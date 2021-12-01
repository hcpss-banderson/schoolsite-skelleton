<?php

namespace Drupal\hcpss_content_scraper\Service;

use Goutte\Client;
use Symfony\Component\DomCrawler\Crawler;

class ScraperService {
  
  /**
   * @var string
   */
  private $url;
  
  /**
   * @var Crawler
   */
  private $crawler;
  
  public function __construct(string $url) {
    $this->url = $url;
    $client = new Client();
    $this->crawler = $client->request('GET', $this->url);
  }
  
  /**
   * Scrape a url.
   * 
   * @param string $url
   * @return array
   */
  public static function scrape(string $url): array {
    $scraper = new self($url);
    return $scraper->scrapeExport();
  }
  
  /**
   * Get the crawler.
   * 
   * @return Crawler
   */
  public function crawl(): Crawler {
    return $this->crawler;
  }
  
  /**
   * Scrape the content-export view.
   * 
   * @return array
   */
  public function scrapeExport(): array {
    $selector = '.view-content-export .views-table tbody tr';
    $rows = [];
    $this->crawler->filter($selector)->each(function (Crawler $tr) use (&$rows) {
      $row = [];
      
      $tr->filter('td')->each(function (Crawler $td) use (&$row) {
        $class = str_replace('export-', '', $td->attr('class'));
        $row[$class] = trim($td->html());
      });
      
      $rows[] = $row;
    });
    
    return $rows;
  }
}
