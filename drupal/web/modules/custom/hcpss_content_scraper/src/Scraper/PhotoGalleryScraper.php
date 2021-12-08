<?php

namespace Drupal\hcpss_content_scraper\Scraper;

use Drupal\hcpss_content_scraper\ScraperBase;
use Drupal\hcpss_content_scraper\ScraperInterface;
use Drupal\hcpss_content_scraper\Service\ScraperService;
use Drupal\node\Entity\Node;
use Drupal\node\NodeInterface;
use Symfony\Component\DomCrawler\Crawler;
use Drupal\taxonomy\TermInterface;
use Drupal\taxonomy\Entity\Term;

class PhotoGalleryScraper extends ScraperBase implements ScraperInterface {

  /**
   * {@inheritDoc}
   * @see \Drupal\hcpss_content_scraper\ScraperInterface::getUrl()
   */
  protected function getUrl(): string {
    return "https://{$this->acronym}.hcpss.org/content-export/gallery";
  }
  
  /**
   * Get an array of the images in the cell.
   * 
   * @param Crawler $item
   * @return array
   */
  protected function getImages(Crawler $cell): array {
    $images = [];
    $cell->filter('ol li')->each(function (Crawler $item) use (&$images) {
      $data = file_get_contents($item->filter('img')->attr('src'));
      $file = file_save_data($data, 'public://' . trim($item->filter('a')->text()));
      
      $images[] = [
        'target_id' => $file->id(),
        'alt'       => $item->filter('img')->attr('alt'),
        'title'     => '',
      ];
    });    
    
    return $images;
  }
  
  /**
   * Get the gallery type by name.
   * 
   * @param string $name
   * @return TermInterface
   */
  protected function getGalleryType(string $name): TermInterface {
    $tids = \Drupal::entityQuery('taxonomy_term')
      ->condition('vid', 'gallery_type')
      ->condition('name', $name)
      ->execute();
    
    return Term::load(array_shift($tids));
  }
  
  /**
   * Create gallert terms.
   */
  protected function makeTerms() {
    $terms = [
      'Celebration'      => 'star',
      'Clubs / Programs' => 'users',
      'Competition'      => 'award',
      'Theatre'          => 'theater-masks',
      'Art'              => 'palette',
    ];
    
    foreach ($terms as $name => $icon) {
      Term::create([
        'vid' => 'gallery_type',
        'name' => $name,
        'field_icon' => [
          'icon_name' => $icon,
          'style' => 'fas',
          'settings' => 'a:3:{s:7:"duotone";a:3:{s:12:"swap-opacity";i:0;s:7:"opacity";a:2:{s:7:"primary";s:0:"";s:9:"secondary";s:0:"";}s:5:"color";a:2:{s:7:"primary";s:7:"#000000";s:9:"secondary";s:7:"#000000";}}s:7:"masking";a:2:{s:4:"mask";s:0:"";s:5:"style";s:0:"";}s:16:"power_transforms";a:3:{s:5:"scale";a:2:{s:4:"type";s:0:"";s:5:"value";s:0:"";}s:10:"position_y";a:2:{s:4:"type";s:0:"";s:5:"value";s:0:"";}s:10:"position_x";a:2:{s:4:"type";s:0:"";s:5:"value";s:0:"";}}}',
        ],
      ])->save();
    }
  }
 
  /**
   * {@inheritDoc}
   * @see \Drupal\hcpss_content_scraper\ScraperInterface::scrape()
   */
  public function scrape(): array {
    $num_created = 0;
    
    $this->makeTerms();
    
    $selector = '.views-table > tbody > tr';
    $scraper = ScraperService::createFromUrl($this->getUrl());
    $scraper->crawl()->filter($selector)->each(function (Crawler $row, $index) use (&$num_created) {
      $images = $this->getImages($row->filter('.export-images'));
      $type_name = trim($row->filter('.content-gallery-taxonomy')->text());
      
      Node::create([
        'type' => 'photo_gallery',
        'uid' => 1,
        'created' => $row->filter('.export-created')->text(),
        'title' => $row->filter('.export-title')->text(),
        'body' => [
          'value' => $row->filter('.export-description')->html(),
          'format' => 'basic_html',
        ],
        'field_photos' => $images,
        'field_gallery_type' => $this->getGalleryType($type_name),
      ])->save();

      $num_created++;
    });
    
    return ['node' => ['photo_gallery' => $num_created]];
  }
}
