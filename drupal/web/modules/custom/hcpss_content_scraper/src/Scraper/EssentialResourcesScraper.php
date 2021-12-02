<?php

namespace Drupal\hcpss_content_scraper\Scraper;

use Drupal\hcpss_content_scraper\ScraperBase;
use Drupal\hcpss_content_scraper\ScraperInterface;
use Drupal\hcpss_content_scraper\Service\ScraperService;
use Drupal\node\Entity\Node;
use Drupal\node\NodeInterface;
use Symfony\Component\DomCrawler\Crawler;
use Drupal\fragments\Entity\Fragment;
use Drupal\entityqueue\Entity\EntitySubqueue;

class EssentialResourcesScraper extends ScraperBase implements ScraperInterface {

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
    $result = [
      'fragment'        => ['resource'           => 0],
      'entity_subqueue' => ['link_resource_list' => 0],
    ];
    
    $scraper = ScraperService::createFromUrl($this->getUrl());
    $scraper->crawl()->filter('.bullet')->each(function (Crawler $bullet) use (&$result) {
      $resources = $this->createResources($bullet);

      $subQueue = EntitySubqueue::create([
        'queue' => 'link_resource_list',
        'name' => $bullet->filter('h2')->text(),
        'field_icon' => [
          "icon_name" => "user-circle",
          "style" => "fas",
        ]
      ]);
      
      foreach ($resources as $resource) {
        $subQueue->addItem($resource);
      }
      
      $subQueue->save();
      $result['entity_subqueue']['link_resource_list']++;
      $result['fragment']['resource'] += count($resources);
    });
    
    return $result;
  }
  
  /**
   * Create the resources within the bullet css class.
   * 
   * @param Crawler $bullet
   * @return array
   */
  protected function createResources(Crawler $bullet): array {
    $resources = [];
    
    $bullet->filter('p')->each(function (Crawler $ptag) use (&$resources) {
      $description = $ptag->html();
      $description = explode('<br>', $description)[1];
      
      $fragment = Fragment::create([
        'type' => 'resource',
        'title' => $ptag->filter('a')->text(),
        'field_link' => [
          'uri' => $ptag->filter('a')->attr('href'),
        ],
        'field_description' => trim($description),
      ]);
      $fragment->save();
      
      $resources[] = $fragment;
    });
    
    return $resources;
  }
}
