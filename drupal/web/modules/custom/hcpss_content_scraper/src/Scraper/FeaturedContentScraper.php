<?php

namespace Drupal\hcpss_content_scraper\Scraper;

use Drupal\hcpss_content_scraper\ScraperBase;
use Drupal\hcpss_content_scraper\ScraperInterface;
use Drupal\hcpss_content_scraper\Service\ScraperService;
use Symfony\Component\DomCrawler\Crawler;
use Drupal\node\Entity\Node;
use Drupal\entityqueue\Entity\EntitySubqueue;

class FeaturedContentScraper extends ScraperBase implements ScraperInterface {
    
  /**
   * {@inheritDoc}
   * @see \Drupal\hcpss_content_scraper\ScraperInterface::getPath()
   */
  protected function getUrl(): string {
    return "https://{$this->acronym}.hcpss.org";
  }
  
  /**
   * {@inheritDoc}
   * @see \Drupal\hcpss_content_scraper\ScraperInterface::scrape()
   */
  public function scrape(): array {
    $queue = EntitySubqueue::load('featured_content');
    
    $scraper = ScraperService::createFromUrl($this->getUrl());
    $selector = '.pane-node a > h1';
    $scraper->crawl()->filter($selector)->each(function (Crawler $header) use ($queue) {
      $query = \Drupal::entityQuery('node')
        ->condition('title', trim($header->text()));
      
      $or = $query->orConditionGroup();
      $or->condition('type', 'news');
      $or->condition('type', 'event');
      
      $query->condition($or);
      $nids = $query->execute();
      
      if (!empty($nids)) {
        $node = Node::load(array_shift($nids));
        $queue->addItem($node);
      }
    });
    
    $queue->save();
    
    return [
      'entity_subqueue' => ['featured_content' => 1],
    ];
  }
}
