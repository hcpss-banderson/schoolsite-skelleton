<?php

namespace Drupal\hcpss_school\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Url;
use Drupal\Component\Serialization\Yaml;
use Drupal\Core\Cache\Cache;

/**
 * Provides a block with resources for athletic participation.
 *
 * @Block(
 *   id = "athletic_participation_resources_block",
 *   admin_label = @Translation("Athletic Participation Resources"),
 *   category = @Translation("HCPSS"),
 * )
 */
class AthleticParticipationResourcesBlock extends BlockBase {
  
  /**
   * {@inheritDoc}
   * @see \Drupal\Core\Block\BlockPluginInterface::build()
   */
  public function build() {
    $path = drupal_get_path('module', 'hcpss_school') . '/data/athletic-resources.yml';
    $resources = Yaml::decode(file_get_contents($path));
    
    $build = [];
    foreach ($resources as $name => $resource) {
      $build[$name] = ['#type' => 'html_tag', '#tag' => 'p'];
      
      $build[$name][] = [
        '#type'   => 'link',
        '#title'  => $resource['title'],
        '#url'    => Url::fromUri($resource['url']),
        '#suffix' => '<br>',  
      ];
      
      $build[$name][] = [
        '#type' => 'html_tag',
        '#tag' => 'span',
        '#value' => $resource['description'],
      ];
    }
    
    $build['#cache']['max-age'] = Cache::PERMANENT;

    return $build;
  }
}
