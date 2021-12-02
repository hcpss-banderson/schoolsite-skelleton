<?php

namespace Drupal\hcpss_content_scraper;

interface ScraperInterface {
  
  /**
   * Perform the scrape.
   * 
   * @return array
   *   An array describing the results:
   *   [
   *     'entity type' => [
   *       'bundle' => '<int: num created>'
   *     ]
   *   ]
   */
  public function scrape(): array;
  
  /**
   * Relative URL to the content.
   * 
   * @return string
   */
  protected function getUrl(): string;
}
