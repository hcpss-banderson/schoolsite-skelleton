<?php

namespace Drupal\hcpss_content_scraper;

use Drupal\taxonomy\TermInterface;
use Drupal\taxonomy\Entity\Term;

abstract class ScraperBase {
  
  /**
   * @var string
   */
  protected $url;
  
  /**
   * @var string
   */
  protected $acronym;
  
  public function __construct(string $acronym = NULL) {
    $acronym = $acronym ?: \Drupal::config('hcpss_school.settings')
      ->get('acronym');
    
    $this->acronym = $acronym;
  }
  
  /**
   * Get the term if it exists, or create it if it does not.
   * 
   * @param string $name
   * @return TermInterface
   */
  public function getOrCreateTerm(string $name): TermInterface {
    $tids = \Drupal::entityQuery('taxonomy_term')
      ->condition('vid', 'tags')
      ->condition('name', $name)
      ->execute();
    
    if (!empty($tids)) {
      $term = Term::load(array_shift($tids));
    } else {
      $term = Term::create(['vid' => 'tags', 'name' => $name]);
      $term->save();
    }
    
    return $term;
  }
}
