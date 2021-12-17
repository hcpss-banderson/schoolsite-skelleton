<?php

namespace Drupal\hcpss_content_scraper\Scraper;

use Drupal\hcpss_content_scraper\ScraperBase;
use Drupal\hcpss_content_scraper\ScraperInterface;
use Drupal\hcpss_content_scraper\Service\ScraperService;
use Symfony\Component\DomCrawler\Crawler;
use Drupal\node\Entity\Node;

class AdvancedPagesScraper extends ScraperBase implements ScraperInterface {
  
  const PANE_TYPE_STAFF_UNKNOWN = 0;
  const PANE_TYPE_STAFF_MEMBER  = 1;
  
  /**
   * {@inheritDoc}
   * @see \Drupal\hcpss_content_scraper\ScraperInterface::getUrl()
   */
  protected function getUrl(): string {
    return "https://{$this->acronym}.hcpss.org";
  }
  
  /**
   * {@inheritDoc}
   * @see \Drupal\hcpss_content_scraper\ScraperInterface::scrape()
   */
  public function scrape(): array { 
    $menu = [];
    
    $scraper = ScraperService::createFromUrl($this->getUrl());
    $scraper->crawl()->filter('.nav-link')->each(function (Crawler $root_item, $root_index) use (&$menu) {
      $menu[$root_index] = [
        'title' => $root_item->children('a')->first()->text(),
        'href' => $root_item->children('a')->first()->attr('href'),
      ];
      
      $this->scrapePage($root_item->children('a')->first()->attr('href'));
      
      if ($root_item->filter('.submenu li')->count()) {
        $root_item->filter('.submenu li')->each(function (Crawler $sub_item, $sub_index) use (&$menu, $root_index) {
          $menu[$root_index]['children'][$sub_index] = [
            'title' => $sub_item->children('a')->first()->text(),
            'href' => $sub_item->children('a')->first()->attr('href'),
          ];
          
          $this->scrapePage($sub_item->children('a')->first()->attr('href'));
        });
      }
    });
    
    return [];
  }
  
  /**
   * Should we scrape the given page?
   * 
   * @param ScraperInterface $scraper
   * @return bool
   */
  protected function shouldScrape(ScraperService $scraper): bool {
    return (bool)$scraper->crawl()->filter('.panel-panel')->count();
  }
  
  /**
   * Get the type of pane.
   * 
   * @param Crawler $pane
   * @return int
   */
  protected function getPaneType(Crawler $pane): int {
    $node = $pane->filter('.node');
    if ($node->count()) {
      $classes = explode(' ', $node->attr('class'));
      
      switch ($classes[1]) {
        case 'node-school-staff-member':
          return self::PANE_TYPE_STAFF_MEMBER;
      }
    }
    
    return self::PANE_TYPE_STAFF_UNKNOWN;
  }
  
  protected function scrapePage($path) {
    if (strpos($path, '/') !== 0) {
      // External link.
      return;
    }
    
    $scraper = ScraperService::createFromUrl($this->getUrl().$path);
    if (!$this->shouldScrape($scraper)) {
      return;
    }
    
    $page = Node::create([
      'type' => 'advanced_page',
      'uid' => 1,
      'title' => $scraper->crawl()->filter('h1')->first()->text(),
    ]);
    
    $scraper->crawl()->filter('.panel-pane')->each(function (Crawler $pane) {
      switch ($this->getPaneType($pane)) {
        case self::PANE_TYPE_STAFF_MEMBER:
          //$paragraph
          break;
      }
    });
  }
}
