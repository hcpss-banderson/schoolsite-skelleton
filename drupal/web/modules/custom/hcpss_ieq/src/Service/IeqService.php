<?php

namespace Drupal\hcpss_ieq\Service;

use Drupal\Component\Utility\UrlHelper;
use Drupal\Component\Serialization\Json;
use GuzzleHttp\Exception\RequestException;

class IeqService {
  
  /**
   * @var string
   */
  private $baseUrl;
  
  /**
   * @var string
   */
  private $acronym;
  
  public function __construct(string $baseUrl, string $acronym) {
    $this->baseUrl = $baseUrl;
    $this->acronym = $acronym;
  }

  /**
   * Fetch the general assessment.
   * 
   * @return array|void|mixed
   */
  public function fetchGeneralAssessments() {
    $query = UrlHelper::buildQuery([
      'filter' => [
        'facility' => ['condition' => [
          'path' => 'field_school.field_acronym',
          'value' => $this->acronym,
        ]],
      ],
      'sort' => '-field_date',
      'page' => ['limit' => 5],
    ]);
    
    $url = $this->baseUrl . '/node/general_assessment?' . $query;
    
    return $this->call($url);
  }
  
  /**
   * Fetch the general assessment.
   *
   * @return array|void|mixed
   */
  public function fetchHvacAssessments() {
    $query = UrlHelper::buildQuery([
      'filter' => [
        'facility' => ['condition' => [
          'path' => 'field_school.field_acronym',
          'value' => $this->acronym,
        ]],
      ],
      'sort' => '-field_date',
      'page' => ['limit' => 5],
    ]);
    
    $url = $this->baseUrl . '/node/hvac_assessment?' . $query;
    
    return $this->call($url);
  }
  
  /**
   * Fetch the walkthroughs.
   * 
   * @return array
   */
  public function fetchWalkthroughs() {
    $query = UrlHelper::buildQuery([
      'filter' => [
        'facility' => ['condition' => [
          'path' => 'field_facility.field_acronym',
          'value' => $this->acronym,
        ]],
        'concern' => ['condition' => [
          'path' => 'field_related_concern',
          'operator' => 'IS NULL',
        ]],
      ],
      'include' => 'field_related_concern',
      'sort' => '-field_date',
      'page' => ['limit' => 5],
    ]);
    
    $url = $this->baseUrl . '/node/walkthrough?' . $query;
    
    return $this->call($url);
  }
  
  /**
   * Fetch the concerns
   * 
   * @return array
   */
  public function fetchConcerns() {
    $url = $this->baseUrl . '/node/concern?';
    $url .= UrlHelper::buildQuery([
      'filter' => ['facility' => ['condition' => [
        'path' => 'field_facility.field_acronym',
        'value' => $this->acronym,
        'sort' => '-field_date_of_report',
      ]]],
      'page' => ['limit' => 5],
    ]);
    
    return $this->call($url);
  }
  
  /**
   * Call the URL, return the data.
   * 
   * @param string $url
   * @return array
   */
  private function call(string $url) {
    try {
      $response = \Drupal::httpClient()->get($url);
      if ($response->getStatusCode() != 200) {
        \Drupal::logger('ieq')
          ->error('There was a problem fetching ieq data.');
        
        return;
      }
      
      $payload = $response->getBody()->getContents();
      $data = Json::decode($payload);
    } catch (RequestException $e) {
      \Drupal::logger('ieq')
        ->error('There was a problem fetching ieq data.');
      
      return;
    }
    
    return $data;
  }
}