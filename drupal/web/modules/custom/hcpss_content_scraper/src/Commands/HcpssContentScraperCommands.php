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
use Drupal\paragraphs\Entity\Paragraph;
use Drupal\fragments\Entity\Fragment;
use Drupal\hcpss_content_scraper\Scraper\DepartmentsScraper;
use Drupal\hcpss_content_scraper\Scraper\EventsScraper;
use Drupal\hcpss_content_scraper\Scraper\PagesScraper;
use Drupal\hcpss_content_scraper\Scraper\NewsScraper;
use Drupal\hcpss_content_scraper\Scraper\PhotoGalleryScraper;
use Drupal\entityqueue\Entity\EntitySubqueue;
use Drupal\hcpss_content_scraper\Scraper\EssentialResourcesScraper;
use Drupal\hcpss_content_scraper\Scraper\FeaturedContentScraper;
use Drupal\hcpss_content_scraper\Scraper\SchoolStaffScraper;
use Drupal\hcpss_content_scraper\Scraper\AdvancedPagesScraper;

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
   * Create everything.
   *
   * @param $acronym
   *   string Argument description.
   * @usage hcpss_content_scraper:scrape-all chs
   *   Scrape the events on the CHS site.
   *
   * @command hcpss_content_scraper:scrape-all
   */
  public function createAll($acronym) {
    $this->scrapeDepartments($acronym);
    $this->scrapeEssentialResources($acronym);
    $this->scrapeEvents($acronym);
    $this->scrapeGalleries($acronym);
    $this->scrapeNews($acronym);
    $this->scrapePages($acronym);
    $this->scrapeFeaturedContent($acronym);
    $this->scrapeSchoolStaff($acronym);
  }
  
  /**
   * Scrape advanced pages.
   *
   * @param $acronym
   *   string Argument description.
   * @usage hcpss_content_scraper:scrape-advanced-pages chs
   *   Scrape the school staff content on the CHS site.
   *
   * @command hcpss_content_scraper:scrape-advanced-pages
   */
  public function scrapeAdvancedPages($acronym) {
    $scraper = new AdvancedPagesScraper($acronym);
    $scraper->scrape();
  }
  
  /**
   * Scrape features content.
   *
   * @param $acronym
   *   string Argument description.
   * @usage hcpss_content_scraper:scrape-school-staff chs
   *   Scrape the school staff content on the CHS site.
   *
   * @command hcpss_content_scraper:scrape-school-staff
   */
  public function scrapeSchoolStaff($acronym) {
    $nids = \Drupal::entityQuery('node')
      ->condition('type', 'advanced_page')
      ->condition('title', 'School Staff')
      ->execute();
    
    if (!empty($nids)) {
      Node::load(reset($nids))->delete();
    }
    
    $scraper = new SchoolStaffScraper($acronym);
    $result = $scraper->scrape();
    
    $this->logger()->success(vsprintf('%d school staff page created.', [
      $result['node']['advanced_page'],
    ]));
  }
  
  /**
   * Scrape features content.
   *
   * @param $acronym
   *   string Argument description.
   * @usage hcpss_content_scraper:scrape-featured chs
   *   Scrape the features content on the CHS site.
   *
   * @command hcpss_content_scraper:scrape-featured
   */
  public function scrapeFeaturedContent($acronym) {
    $featured = EntitySubqueue::load('featured_content')->clearItems();
    $featured->save();
    
    $scraper = new FeaturedContentScraper($acronym);
    $result = $scraper->scrape();
    
    $this->logger()->success(vsprintf('%d featured content created.', [
      $result['entity_subqueue']['featured_content'],
    ]));
  }
  
  /**
   * Create the staff list page.
   *
   * @param $acronym
   *   string Argument description.
   * @usage hcpss_content_scraper:scrape-departments chs
   *   Scrape the events on the CHS site.
   *
   * @command hcpss_content_scraper:scrape-departments
   */
  public function scrapeDepartments($acronym) {
    $this->deleteAll('fragment', ['type' => 'department']);
    
    $scraper = new DepartmentsScraper($acronym);
    $result = $scraper->scrape();
    
    $this->logger()->success(vsprintf('%d departments and %d staff members created', [
      $result['fragment']['department'],
      $result['paragraph']['staff_member'],
    ]));
  }
  
  /**
   * Scrape events
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
    
    $scraper = new EventsScraper($acronym);
    $result  = $scraper->scrape();
    
    $this->logger()->success($result['node']['event'] . ' events created.');
  }
  
  /**
   * Scrape pages.
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
    
    $scraper = new PagesScraper($acronym);
    $result  = $scraper->scrape();
    
    $this->logger()->success(dt($result['node']['basic_page'] . ' paged created.'));
  }
  
  /**
   * Scrape news.
   *
   * @param $acronym
   *   string Argument description.
   * @usage hcpss_content_scraper:scrape-news chs
   *   Scrape the news on the CHS site.
   *
   * @command hcpss_content_scraper:scrape-news
   */
  public function scrapeNews($acronym) {
    $this->deleteAll('node', ['type' => 'news']);
    
    $scraper = new NewsScraper($acronym);
    $result  = $scraper->scrape();
    
    $this->logger()->success(dt($result['node']['news'] . ' news nodes created.'));
  }
  
  /**
   * Scrape galleries.
   *
   * @param $acronym
   *   string Argument description.
   * @usage hcpss_content_scraper:scrape-galleries chs
   *   Scrape the galleries on the CHS site.
   *
   * @command hcpss_content_scraper:scrape-galleries
   */
  public function scrapeGalleries($acronym) {
    $this->deleteAll('node', ['type' => 'photo_gallery']);
    $this->deleteAll('taxonomy_term', ['vid' => 'gallery_type']);
    
    $scraper = new PhotoGalleryScraper($acronym);
    $result  = $scraper->scrape();
    
    $this->logger()->success(dt($result['node']['photo_gallery'] . ' galleries created.'));
  }
  
  /**
   * Scrape essential resources.
   *
   * @param $acronym
   *   string Argument description.
   * @usage hcpss_content_scraper:scrape-essential-resources chs
   *   Scrape the essential resources on the CHS site.
   *
   * @command hcpss_content_scraper:scrape-essential-resources
   */
  public function scrapeEssentialResources($acronym) {
    $this->deleteAll('entity_subqueue', ['queue' => 'link_resource_list']);
    $this->deleteAll('fragment', ['type' => 'resource']);
    
    $scraper = new EssentialResourcesScraper($acronym);
    $result = $scraper->scrape();
    
    $this->logger()->success(vsprintf('%d resources and %d lists created.', [
      $result['fragment']['resource'],
      $result['entity_subqueue']['link_resource_list'],
    ]));
  }
  
//   /**
//    * Command to scrape the main menu.
//    *
//    * @param string $acronym
//    * @usage hcpss_content_scraper:scrape-menu chs
//    *   Scrape the menu from the CHS site.
//    *
//    * @command hcpss_content_scraper:scrape-menu
//    */
//   public function scrapeMenu($acronym) {
//     $url = "https://{$acronym}.hcpss.org";
//     $scraper = new ScraperService($url);
//     $selector = '.centered-navigation nav > ul > li';
//     $scraper->crawl()->filter($selector)->each(function (Crawler $level) use ($acronym) {
//       $this->handleMenuItem($level, $acronym);
//     });
//   }
  
//   /**
//    * Create a page from the URL.
//    *
//    * @param string $url
//    * @return NodeInterface|NULL
//    */
//   private function createPage(string $alias, string $acronym): ?NodeInterface {
//     $scraper = new ScraperService("https://{$acronym}.hcpss.org{$alias}");
    
//     $panes = $scraper->crawl()->filter('.panel-pane');
//     if ($panes->count()) {
//       $html = '';
//       $panes->each(function (Crawler $pane) use ($html) {
//         $classes = explode(' ', $pane->attr('class'));
//         if (in_array('pane-node', $classes)) {
//           if ($title = $pane->filter('.pane-title')) {
//             $html .= '<h2>' . $title->text() . '</h2>';
//           }
          
//           if ($content = $pane->filter('.content')) {
//             $html .= $content->html();
//           }
//         } else if (in_array('pane-custom', $classes)) {
//           if ($content = $pane->filter('.pane-content')) {
//             $html .= $content->html();
//           }
//         }
//       });
        
//         $node = Node::create([
//           'type' => 'page',
//           'uid' => 1,
//           'title' => $scraper->filter('h1')->text(),
//           'body' => ['format' => 'basic_html', 'value' => $html],
//           'path' => ['alias' => $row['path']],
//         ]);
        
//         $node->save();
//         return $node;
//     }
    
//     return NULL;
//   }
  
//   private function handleMenuItem(Crawler $level, string $acronym, MenuItemExtrasMenuLinkContentInterface $parent = NULL) {
//     $a = $level->filter('& > a');
//     $label = $a->text();
//     $href = $a->attr('href');
    
//     if (strpos($href, '/') === 0) {
//       $params = Url::fromUserInput($href)->getRouteParameters();
//       $entity_type = key($params);
//       $node = \Drupal::entityTypeManager()
//       ->getStorage($entity_type)
//       ->load($params[$entity_type]);
      
//       if (!$node) {
//         $node = $this->createPage("https://{$acronym}.hcpss.org{$href}");
//       }
      
//       if ($node) {
//         $link = MenuLinkContent::create([
//           'title' => $label,
//           'link' => ['uri' => 'entity:node/' . $node->id()],
//           'menu_name' => 'main',
//           'weight' => 0,
//         ]);
        
//         if ($parent) {
//           $link->parent = $parent;
//         }
//       }
      
      
      
//       $path = \Drupal::service('path.alias_manager')->getPathByAlias($href);
      
//       if(preg_match('/node\/(\d+)/', $path, $matches)) {
//         $node = \Drupal\node\Entity\Node::load($matches[1]);
//       }
      
      
      
//       // Internal page.
//       $scraper = new ScraperService("https://{$acronym}.hcpss.org{$href}");
      
//     } else {
//       // External page.
//       MenuLinkContent::create([
//         'title' => $label,
//         'link' => ['uri' => $href],
//         'menu_name' => 'main',
//         'weight' => 0,
//       ]);
//     }
//   }
}
