<?php

namespace Drupal\hcpss_school\Service;

use Drupal\hcpss_school\ApiDataAwareTrait;

class ApiDataService {
  use ApiDataAwareTrait;
  
  /**
   * Expose API data.
   * 
   * @return array
   */
  public function data(): array {
    return self::getData();
  }
}
