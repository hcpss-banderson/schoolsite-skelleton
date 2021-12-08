<?php

namespace Drupal\hcpss_content_scraper\Scraper;

use Drupal\hcpss_content_scraper\ScraperBase;
use Drupal\hcpss_content_scraper\ScraperInterface;
use Drupal\hcpss_content_scraper\Service\ScraperService;
use Drupal\node\Entity\Node;

class PagesScraper extends ScraperBase implements ScraperInterface {
  
  /**
   * {@inheritDoc}
   * @see \Drupal\hcpss_content_scraper\ScraperInterface::getUrl()
   */
  protected function getUrl(): string {
    return "https://{$this->acronym}.hcpss.org/content-export/basic_page";
  }
  
  /**
   * {@inheritDoc}
   * @see \Drupal\hcpss_content_scraper\ScraperInterface::scrape()
   */
  public function scrape(): array {    
    $rows = ScraperService::scrape($this->getUrl());
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
    
    return ['node' => ['basic_page' => $num_created]];
  }
}
