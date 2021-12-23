<?php

namespace Drupal\hcpss_content_scraper\Scraper;

use Drupal\hcpss_content_scraper\ScraperBase;
use Drupal\hcpss_content_scraper\ScraperInterface;
use Drupal\hcpss_content_scraper\Service\ScraperService;
use Symfony\Component\DomCrawler\Crawler;
use Drupal\node\Entity\Node;
use Drupal\paragraphs\Entity\Paragraph;
use GuzzleHttp\Client;
use Drupal\Core\Url;
use Drupal\fragments\Entity\Fragment;

class AdvancedPagesScraper extends ScraperBase implements ScraperInterface {
  
  const PANE_TYPE_STAFF_UNKNOWN  = 0;
  const PANE_TYPE_STAFF_MEMBER   = 1;
  const PANE_TYPE_PAGE_SECTION   = 2;
  const PANE_TYPE_RESOURCE_GROUP = 3;
  
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
    $return = ['node' => ['advanced_page' => 0]];
    $menu = [];
    
    $scraper = ScraperService::createFromUrl($this->getUrl());
    $scraper->crawl()->filter('.nav-link')->each(function (Crawler $root_item, $root_index) use (&$menu, &$return) {
      $menu[$root_index] = [
        'title' => $root_item->children('a')->first()->text(),
        'href' => $root_item->children('a')->first()->attr('href'),
      ];
      
      if ($this->scrapePage($root_item->children('a')->first()->attr('href'))) {
        $return['node']['advanced_page']++;
      }
      
      if ($root_item->filter('.submenu li')->count()) {
        $root_item->filter('.submenu li')->each(function (Crawler $sub_item, $sub_index) use (&$menu, $root_index) {
          $menu[$root_index]['children'][$sub_index] = [
            'title' => $sub_item->children('a')->first()->text(),
            'href' => $sub_item->children('a')->first()->attr('href'),
          ];
          
          if ($this->scrapePage($sub_item->children('a')->first()->attr('href'))) {
            $return['node']['advanced_page']++;
          }
        });
      }
    });
    
    return $return;
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
        case 'node-page-section':
          return self::PANE_TYPE_PAGE_SECTION;
        case 'node-link-resource-group':
          return self::PANE_TYPE_RESOURCE_GROUP;
      }
    }
    
    return self::PANE_TYPE_STAFF_UNKNOWN;
  }
  
  protected function scrapePage($path) {    
    if (strpos($path, '/') !== 0) {
      // External link.
      return;
    }
    
    if ($path == '/') {
      // Homepage is handled seperatly.
      return;
    }
    
    $url_object = \Drupal::service('path.validator')->getUrlIfValid($path);
    if ($url_object) {
      // We already have something at this URL.
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
      'path' => ['alias' => $path],
    ]);
    
    $scraper->crawl()->filter('.panel-pane')->each(function (Crawler $pane) use ($page) {
      switch ($this->getPaneType($pane)) {
        case self::PANE_TYPE_STAFF_MEMBER:
          $paragraph = Paragraph::create([
            'type' => 'staff_member',
            'field_name' => $pane->filter('.pane-title')->text(),
            'field_job_title' => $pane->filter('.field-name-field-staff-job-title')->text(),
            'field_email' => $pane->filter('.field-name-field-staff-email a')->text(),
          ]);
          
          $img = $pane->filter('img');
          if ($img->count()) {
            $path         = $img->attr('src');
            $file         = file_get_contents($path);
            $file_manager = \Drupal::service('file.repository');
            $file_entity  = $file_manager->writeData($file, 'public://'.basename($path));
            
            $paragraph->field_picture = $file_entity;
          }
          
          $bio = $pane->filter('.field-name-field-staff-biography');
          if ($bio->count()) {
            $paragraph->field_biography = [
              'value' => $bio->text(),
              'format' => 'basic_html',
            ];
          }
          
          $paragraph->save();
          $page->field_parts[] = $paragraph;
          break;
        case self::PANE_TYPE_PAGE_SECTION:
          $panel_title = $pane->filter('.pane-title');
          if ($panel_title->count()) {
            $heading = Paragraph::create([
              'type' => 'heading',
              'field_level' => 2,
              'field_text' => $panel_title->text(),
            ]);
            $heading->save();
            $page->field_parts[] = $heading;
          }
          
          $content = $pane->filter('.content > div');
          if ($content->count()) {
            $text = Paragraph::create([
              'type' => 'rich_text',
              'field_rich_text' => [
                'format' => 'basic_html',
                'value' => $content->html(),
              ],
            ]);
            $text->save();
            $page->field_parts[] = $text;
          }
          break;
        case self::PANE_TYPE_RESOURCE_GROUP:
          $panel_title = $pane->filter('.pane-title');
          if ($panel_title->count()) {
            $heading = Paragraph::create([
              'type' => 'heading',
              'field_level' => 2,
              'field_text' => $panel_title->text(),
            ]);
            $heading->save();
            $page->field_parts[] = $heading;
          }
          
          $description = $pane->filter('.field-name-field-link-group-description .field-item');
          if ($description->count()) {
            $text = Paragraph::create([
              'type' => 'rich_text',
              'field_rich_text' => [
                'format' => 'basic_html',
                'value' => $description->text(),
              ],
            ]);
            $text->save();
            print_r($text->toArray());
            $page->field_parts[] = $text;
          }
          
          $resources = $pane->filter('.field-name-field-link-resources');
          if ($resources->count()) {
            $resources->each(function (Crawler $resource) use ($page) {
              $link = $resource->filter('.field-name-field-link--url a');
              $description = $resource->filter('.field-name-field-link-description .field-item');
              
              $fids = \Drupal::entityQuery('fragment')
                ->condition('type', 'resource')
                ->condition('title', $link->text())
                ->execute();
              
              $fragment = !empty($fids) ? Fragment::load(reset($fids)) : NULL;
              if (!$fragment) {
                $fragment = Fragment::create([
                  'type' => 'resource',
                  'title' => $link->text(),
                  'field_link' => ['uri' => $link->attr('href')],
                  'field_description' => $description->text(),
                ]);
                $fragment->save();
              }
              
              $paragraph = Paragraph::create([
                'type' => 'resource',
                'field_resource' => $fragment,
              ]);
              $paragraph->save();
              $page->field_parts[] = $paragraph;
            });
          }
          break;
          
      }
    });
    
    $page->save();
    return 1;
  }
}
