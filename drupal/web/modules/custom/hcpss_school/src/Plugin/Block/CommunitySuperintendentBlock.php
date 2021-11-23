<?php

namespace Drupal\hcpss_school\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\hcpss_school\ApiDataAwareTrait;
use Drupal\Core\Cache\Cache;

/**
 * Provides a Community Superintendent Block.
 *
 * @Block(
 *   id = "community_superintendent_block",
 *   admin_label = @Translation("Community Superintendent"),
 *   category = @Translation("HCPSS"),
 * )
 */
class CommunitySuperintendentBlock extends BlockBase {
  
  use ApiDataAwareTrait;
  
  public function build() {
    $build = [];
    
    $build[] = [
      '#markup' => '
        <p>With a focus on instruction as a birth-through-graduation continuum, 
        Community Superintendents better position the school system to support 
        students throughout their entire journey with HCPSS. 
        <a href="http://www.hcpss.org/contact-us/community-superintendents/">View 
        community superintendents by regional area</a>.</p>
      ',
    ];
    
    $data = self::getData();
    
    $build[] = [
      '#markup' => vsprintf('<p><strong>Community Superintendent Area %d</strong>: %s</p>', [
        $data['administrative_cluster']['cluster'],
        $data['administrative_cluster']['community_superintendent']['name'],
      ]),
    ];
    
    $build['#cache']['max-age'] = Cache::PERMANENT;
    
    return $build;    
  }
}
