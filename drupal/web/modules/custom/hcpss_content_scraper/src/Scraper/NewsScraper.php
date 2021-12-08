<?php

namespace Drupal\hcpss_content_scraper\Scraper;

use Drupal\hcpss_content_scraper\ScraperBase;
use Drupal\hcpss_content_scraper\ScraperInterface;
use Drupal\hcpss_content_scraper\Service\ScraperService;
use Drupal\node\Entity\Node;
use Drupal\node\NodeInterface;

class NewsScraper extends ScraperBase implements ScraperInterface {

  /**
   * {@inheritDoc}
   * @see \Drupal\hcpss_content_scraper\ScraperInterface::getUrl()
   */
  protected function getUrl(): string {
    return "https://{$this->acronym}.hcpss.org/content-export/news_message";
  }
 
  /**
   * {@inheritDoc}
   * @see \Drupal\hcpss_content_scraper\ScraperInterface::scrape()
   */
  public function scrape(): array {
    $num_created = 0;
    
    $rows = ScraperService::scrape($this->getUrl());    
    foreach ($rows as $row) {
      if (empty($row['title'])) {
        print_r($row);
      }
      
      $body = [
        'value' => $row['message-content'],
        'format' => 'basic_html',
      ];
      
      if ($row['message-summary']) {
        $body['summary'] = $row['message-summary'];
      }
      
      $node = Node::create([
        'type' => 'news',
        'uid' => 1,
        'created' => $row['created'],
        'title' => $row['title'],
        'body' => $body,
      ]);
      
      $this->addTags($node, $row);
      
      $node->save();
      $num_created++;
    }
    
    return ['node' => ['news' => $num_created]];
  }

  /**
   * Add tags to the node.
   * 
   * @param NodeInterface $node
   * @param array $row
   */
  protected function addTags(NodeInterface $node, array $row) {
    if (!empty($row['tags'])) {
      $tags = explode(',', $row['tags']);
      if (!empty($tags)) {
        foreach ($tags as $tag) {
          $name = trim($tag);
          if ($name) {
            $node->field_tags[] = $this->getOrCreateTerm($name);
          }
        }
      }
    }
  }
}
