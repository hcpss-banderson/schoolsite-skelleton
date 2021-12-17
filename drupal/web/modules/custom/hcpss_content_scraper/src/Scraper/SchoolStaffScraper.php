<?php

namespace Drupal\hcpss_content_scraper\Scraper;

use Drupal\hcpss_content_scraper\ScraperBase;
use Drupal\hcpss_content_scraper\ScraperInterface;
use Drupal\hcpss_content_scraper\Service\ScraperService;
use Symfony\Component\DomCrawler\Crawler;
use Drupal\node\Entity\Node;
use Drupal\fragments\Entity\Fragment;
use Drupal\paragraphs\Entity\Paragraph;
use Drupal\menu_link_content\Entity\MenuLinkContent;

class SchoolStaffScraper extends ScraperBase implements ScraperInterface {
  
  /**
   * {@inheritDoc}
   * @see \Drupal\hcpss_content_scraper\ScraperInterface::getPath()
   */
  protected function getUrl(): string {
    return "https://{$this->acronym}.hcpss.org/school-staff";
  }
  
  /**
   * {@inheritDoc}
   * @see \Drupal\hcpss_content_scraper\ScraperInterface::scrape()
   */
  public function scrape(): array {
    $page = Node::create([
      'type' => 'advanced_page',
      'title' => 'School Staff',
      'uid' => 1,
      'path' => ['alias' => '/school-staff'],
    ]);
    
    ScraperService::createFromUrl($this->getUrl())->crawl()
      ->filter('.node h2')
      ->each(function (Crawler $header) use ($page) {
        $title = trim($header->text());
        
        $fids = \Drupal::entityQuery('fragment')
          ->condition('type', 'department')
          ->condition('title', $title)
          ->execute();
        
        if (!empty($fids)) {
          $fragment = Fragment::load(reset($fids));
          $paragraph = Paragraph::create([
            'type' => 'department',
            'field_department' => $fragment,
          ]);
          $paragraph->save();
          
          $page->field_parts[] = $paragraph;
        }
      }
    );
      
    $page->save();
    
    MenuLinkContent::create([
      'title' => 'Our Staff',
      'link' => ['uri' => 'entity:node/' . $page->id()],
      'menu_name' => 'main',
      'weight' => 1,
    ])->save();
    
    return ['node' => ['advanced_page' => 1]];
  }
}
