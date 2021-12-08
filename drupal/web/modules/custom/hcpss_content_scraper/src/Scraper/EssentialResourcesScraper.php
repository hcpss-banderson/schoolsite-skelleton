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

      $title = $bullet->filter('h2')->text();
      $name = strtolower(str_replace(' ', '_', $title));
      
      $subQueue = EntitySubqueue::create([
        'queue' => 'link_resource_list',
        'name' => $name,
        'title' => $title,
        'items' => $resources,
        'field_icon' => [
          "icon_name" => "user-circle",
          "style" => "fas",
          'settings' => 'a:3:{s:7:"duotone";a:3:{s:12:"swap-opacity";i:0;s:7:"opacity";a:2:{s:7:"primary";s:0:"";s:9:"secondary";s:0:"";}s:5:"color";a:2:{s:7:"primary";s:7:"#000000";s:9:"secondary";s:7:"#000000";}}s:7:"masking";a:2:{s:4:"mask";s:0:"";s:5:"style";s:0:"";}s:16:"power_transforms";a:3:{s:5:"scale";a:2:{s:4:"type";s:0:"";s:5:"value";s:0:"";}s:10:"position_y";a:2:{s:4:"type";s:0:"";s:5:"value";s:0:"";}s:10:"position_x";a:2:{s:4:"type";s:0:"";s:5:"value";s:0:"";}}}',
        ]
      ]);
      
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
