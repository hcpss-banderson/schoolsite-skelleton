<?php

namespace Drupal\hcpss_content_scraper\Scraper;

use Drupal\hcpss_content_scraper\ScraperInterface;
use Drupal\hcpss_content_scraper\ScraperBase;
use Drupal\hcpss_content_scraper\Service\ScraperService;
use Drupal\node\Entity\Node;

class EventsScraper extends ScraperBase implements ScraperInterface {
  
  /**
   * {@inheritDoc}
   * @see \Drupal\hcpss_content_scraper\ScraperInterface::getUrl()
   */
  protected function getUrl(): string {
    return "https://{$this->acronym}.hcpss.org/content-export/event";
  }
  
  /**
   * {@inheritDoc}
   * @see \Drupal\hcpss_content_scraper\ScraperInterface::scrape()
   */
  public function scrape(): array {
    $rows = ScraperService::scrape($this->getUrl());
    $num_created = 0;
    foreach ($rows as $row) {
      list($start, $end) = $this->normalizeDate(strip_tags($row['event-date']));
      
      Node::create([
        'type' => 'event',
        'uid' => 1,
        'title' => htmlspecialchars_decode($row['title']),
        'body' => [
          'value' => $row['description'],
          'format' => 'basic_html',
        ],
        'field_when' => $this->convertDateToWhen($start, $end),
        'created' => $row['created'],
      ])->save();
      $num_created++;
    }
    
    return ['node' => ['event' => $num_created]];
  }
  
  /**
   * Convert a date time string into a pair of timestamps for the start time
   * and the end time.
   *
   * @param string $date
   *  A string in format:
   *  "Tue, 31 Jan 2017 07:00 EST"
   *  "Wed, 27 Apr 2022 (All day)"
   *  "Wed, 10 Nov 2021 07:30 to 14:00 EST"
   *  "Wed, 20 Apr 2022 08:00 EDT to Mon, 25 Apr 2022 08:30 EDT"
   * @return int[]
   *   The pair of timestamps, start and end.
   */
  private function normalizeDate(string $date): array {
    $date  = str_replace([' EDT', ' EST'], '', $date);
    
    $num_all_days = substr_count($date, '(All day)');
    switch ($num_all_days) {
      case 1:
        $date = str_replace('(All day)', '00:00 to 23:59', $date);
        break;
      case 2:
        $index1 = strpos($date, '(All day)');
        $date = substr_replace($date, '00:00', $index1, 9);
        $index2 = strpos($date, '(All day)');
        $date = substr_replace($date, '23:59', $index2, 9);
        break;
    }
    
    $parts = explode(' ', $date);
    if (count($parts) < 11) {
      // Start date and end date are the same.
      $start_date = implode(' ', [$parts[0], $parts[1], $parts[2], $parts[3]]);
      if (empty($parts[5])) {
        // No end time is specified.
        $format = 'D, d M Y H:i';
        $start = \DateTime::createFromFormat($format, implode(' ', $parts));
        $start->add(new \DateInterval('PT1H'));
        $parts[5] = 'to';
        $parts[6] = $start->format('H:i');
      }
      $parts[5] .= " {$start_date}";
    }
    $date = implode(' ', $parts);
    
    // Now we know we have a date string formatted like this:
    // "Wed, 20 Apr 2022 08:00 to Mon, 25 Apr 2022 08:30".
    $tz = new \DateTimeZone('America/New_York');
    return array_map(function ($value) use ($tz) {
      $format = 'D, d M Y H:i';
      $date_time = \DateTimeImmutable::createFromFormat($format, $value, $tz);
      
      return $date_time->getTimestamp();
    }, explode(' to ', $date));
  }
  
  /**
   * Convert the date string to an array format compatible with the field_when.
   *
   * @param string $date
   *  A string in format:
   *  "Wed, 20 Apr 2022 08:00 to Mon, 25 Apr 2022 08:30"
   * @return array
   */
  private function convertDateToWhen(int $start, int $end): array {
    return [
      'value'       => $start,
      'end_value'   => $end,
      'duration'    => $end - $start,
      'rrule'       => null,
      'rrule_index' => null,
      'timezone'    => '',
    ];
  }
}
