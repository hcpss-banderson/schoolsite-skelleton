<?php

namespace Drupal\hcpss_school;

use Drupal\Component\Serialization\Json;

trait ApiDataAwareTrait {
  
  /**
   * @var array
   */
  private static $data = [];
  
  /**
   * Get school data.
   * 
   * @return array
   */
  protected static function getData(): array {
    if (empty(self::$data)) {
      $acronym = \Drupal::config('hcpss_school.settings')->get('acronym');
      $payload = file_get_contents("https://api.hocoschools.org/schools/{$acronym}.json");
      self::$data = Json::decode($payload);
    }
    
    return self::$data;
  }  
}
