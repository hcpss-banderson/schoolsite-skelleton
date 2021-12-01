<?php

namespace Drupal\hcpss_content_scraper\Service;

use Goutte\Client;
use Symfony\Component\DomCrawler\Crawler;

class ScraperService {
  
  /**
   * @var Crawler
   */
  private $crawler;
  
  public function __construct(Crawler $crawler) {    
    $this->crawler = $crawler;
  }
  
  /**
   * Create a scraper service from a URL.
   * 
   * @param string $url
   * @return self
   */
  public static function createFromUrl(string $url): self {
    $client = new Client();
    $scraperService = new self($client->request('GET', $url));
    
    return $scraperService;
  }
  
  /**
   * Scrape a url.
   * 
   * @param string $url
   * @return array
   */
  public static function scrape(string $url): array {
    $client = new Client();
    $scraper = new self($client->request('GET', $url));
    
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
   * Extract text from crawler without worrying about "empty node list" error.
   * 
   * @param string $selector
   * @return string|NULL
   */
  public function safeText(string $selector): ?string {
    if ($this->crawler->filter($selector)->count() == 0) {
      return NULL;
    }
    
    return $this->crawler->filter($selector)->text();
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
