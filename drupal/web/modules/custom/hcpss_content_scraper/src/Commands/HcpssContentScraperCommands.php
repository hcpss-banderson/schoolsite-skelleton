<?php

namespace Drupal\hcpss_content_scraper\Commands;

use Consolidation\OutputFormatters\StructuredData\RowsOfFields;
use Drush\Commands\DrushCommands;
use Drupal\hcpss_content_scraper\Service\ScraperService;
use Drupal\node\Entity\Node;
use Symfony\Component\DomCrawler\Crawler;
use Drupal\menu_link_content\Entity\MenuLinkContent;
use Drupal\Core\Url;
use Drupal\menu_item_extras\Entity\MenuItemExtrasMenuLinkContentInterface;
use Drupal\node\NodeInterface;

/**
 * A Drush commandfile.
 *
 * In addition to this file, you need a drush.services.yml
 * in root of your module, and a composer.json file that provides the name
 * of the services file to use.
 *
 * See these files for an example of injecting Drupal services:
 *   - http://cgit.drupalcode.org/devel/tree/src/Commands/DevelCommands.php
 *   - http://cgit.drupalcode.org/devel/tree/drush.services.yml
 */
class HcpssContentScraperCommands extends DrushCommands {

  /**
   * Delete all entities meeting the give properties.
   * 
   * @param string $entity_type
   * @param array $properties
   */
  private function deleteAll(string $entity_type, array $properties) {
    $storage = \Drupal::entityTypeManager()->getStorage($entity_type);
    $entities = $storage->loadByProperties($properties);
    $storage->delete($entities);
  }
  
  /**
   * Create a page from the URL.
   * 
   * @param string $url
   * @return NodeInterface|NULL
   */
  private function createPage(string $alias, string $acronym): ?NodeInterface {
    $scraper = new ScraperService("https://{$acronym}.hcpss.org{$alias}");
    
    $panes = $scraper->crawl()->filter('.panel-pane');
    if ($panes->count()) {
      $html = '';
      $panes->each(function (Crawler $pane) use ($html) {
        $classes = explode(' ', $pane->attr('class'));
        if (in_array('pane-node', $classes)) {
          if ($title = $pane->filter('.pane-title')) {
            $html .= '<h2>' . $title->text() . '</h2>';
          }
          
          if ($content = $pane->filter('.content')) {
            $html .= $content->html();  
          }
        } else if (in_array('pane-custom', $classes)) {
          if ($content = $pane->filter('.pane-content')) {
            $html .= $content->html();
          }
        }
      });
      
      $node = Node::create([
        'type' => 'page',
        'uid' => 1,
        'title' => $scraper->filter('h1')->text(),
        'body' => ['format' => 'basic_html', 'value' => $html],
        'path' => ['alias' => $row['path']],
      ]);
      
      $node->save();
      return $node;
    }
    
    return NULL;
  }
  
  private function handleMenuItem(Crawler $level, string $acronym, MenuItemExtrasMenuLinkContentInterface $parent = NULL) {
    $a = $level->filter('& > a');
    $label = $a->text();
    $href = $a->attr('href');
    
    if (strpos($href, '/') === 0) {
      $params = Url::fromUserInput($href)->getRouteParameters();
      $entity_type = key($params);
      $node = \Drupal::entityTypeManager()
        ->getStorage($entity_type)
        ->load($params[$entity_type]);
      
      if (!$node) {
        $node = $this->createPage("https://{$acronym}.hcpss.org{$href}");  
      }

      if ($node) {
        $link = MenuLinkContent::create([
          'title' => $label,
          'link' => ['uri' => 'entity:node/' . $node->id()],
          'menu_name' => 'main',
          'weight' => 0,
        ]);
        
        if ($parent) {
          $link->parent = $parent;
        }
      }

      
      
      $path = \Drupal::service('path.alias_manager')->getPathByAlias($href);
      
      if(preg_match('/node\/(\d+)/', $path, $matches)) {
        $node = \Drupal\node\Entity\Node::load($matches[1]);
      }
      
      
      
      // Internal page.
      $scraper = new ScraperService("https://{$acronym}.hcpss.org{$href}");
      
    } else {
      // External page.
      MenuLinkContent::create([
        'title' => $label,
        'link' => ['uri' => $href],
        'menu_name' => 'main',
        'weight' => 0,
      ]);
    }
  }
  
  /**
   * Command to scrape the main menu.
   * 
   * @param string $acronym
   * @usage hcpss_content_scraper:scrape-menu chs
   *   Scrape the menu from the CHS site.
   *
   * @command hcpss_content_scraper:scrape-menu
   */
  public function scrapeMenu($acronym) {
    $url = "https://{$acronym}.hcpss.org";
    $scraper = new ScraperService($url);
    $selector = '.centered-navigation nav > ul > li';
    $scraper->crawl()->filter($selector)->each(function (Crawler $level) use ($acronym) {
      $this->handleMenuItem($level, $acronym);
    });
  }
  
  /**
   * Command description here.
   *
   * @param $acronym
   *   string Argument description.
   * @usage hcpss_content_scraper:scrape-events chs
   *   Scrape the events on the CHS site.
   *
   * @command hcpss_content_scraper:scrape-events
   */
  public function scrapeEvents($acronym) {
    $this->deleteAll('node', ['type' => 'event']);
    
    $url = "https://{$acronym}.hcpss.org/content-export/event";
    $rows = ScraperService::scrape($url);
    $num_created = 0;
    foreach ($rows as $row) {
      list($start, $end) = $this->normalizeDate(strip_tags($row['event-date']));
      
      echo "\n".htmlspecialchars_decode($row['title'])."\n";
      
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
    
    $this->logger()->success(dt($num_created . ' events created.'));
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
    
    // Now we know we have a date string formatted like this: 
    // "Wed, 20 Apr 2022 08:00 to Mon, 25 Apr 2022 08:30".
    $tz = new \DateTimeZone('America/New_York');
    return array_map(function ($value) use ($tz) {
      $format = 'D, d M Y H:i';
      $date_time = \DateTimeImmutable::createFromFormat($format, $value, $tz);
      
      return $date_time->getTimestamp();
    }, explode(' to ', implode(' ', $parts)));
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
  
  /**
   * Command description here.
   *
   * @param $acronym
   *   string Argument description.
   * @usage hcpss_content_scraper:scrape-pages chs
   *   Scrape the main menu on the CHS site.
   *
   * @command hcpss_content_scraper:scrape-pages
   */
  public function scrapePages($acronym) {
    $this->deleteAll('node', ['type' => 'page']);
    
    $url = "https://{$acronym}.hcpss.org/content-export/basic_page";
    $scraper = new ScraperService($url);
    
    $rows = $scraper->scrapeExport();
    $num_created = 0;
    foreach ($rows as $row) {
      Node::create([
        'type' => 'page',
        'uid' => 1,
        'title' => htmlspecialchars_decode($row['title']),
        'path' => ['alias' => $row['path']],
        'body' => [
          'value' => $row['description'],
          'format' => 'basic_html',
        ],
        'created' => $row['created'],
      ])->save();
      $num_created++;
    }
    
    $this->logger()->success(dt($num_created . ' paged created.'));
  }

  /**
   * An example of the table output format.
   *
   * @param array $options An associative array of options whose values come from cli, aliases, config, etc.
   *
   * @field-labels
   *   group: Group
   *   token: Token
   *   name: Name
   * @default-fields group,token,name
   *
   * @command hcpss_content_scraper:token
   * @aliases token
   *
   * @filter-default-field name
   * @return \Consolidation\OutputFormatters\StructuredData\RowsOfFields
   */
  public function token($options = ['format' => 'table']) {
    $all = \Drupal::token()->getInfo();
    foreach ($all['tokens'] as $group => $tokens) {
      foreach ($tokens as $key => $token) {
        $rows[] = [
          'group' => $group,
          'token' => $key,
          'name' => $token['name'],
        ];
      }
    }
    return new RowsOfFields($rows);
  }
}
