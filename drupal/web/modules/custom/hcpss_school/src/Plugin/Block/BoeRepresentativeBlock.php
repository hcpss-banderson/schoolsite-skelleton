<?php

namespace Drupal\hcpss_school\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\hcpss_school\ApiDataAwareTrait;
use Drupal\Core\Cache\Cache;

/**
 * Provides a Community Superintendent Block.
 *
 * @Block(
 *   id = "boe_representative_block",
 *   admin_label = @Translation("BOE Representative"),
 *   category = @Translation("HCPSS"),
 * )
 */
class BoeRepresentativeBlock extends BlockBase {
  
  use ApiDataAwareTrait;
  
  public function build() {
    $build = [];
    
    $build[] = [
      '#markup' => '
        <p>Howard County public schools are divided into school clusters, which 
        are assigned to individual Board members to facilitate school 
        visitations, attend special events, and provide a point of contact for 
        each school community. 
        <a href="http://www.boarddocs.com/mabe/hcpssmd/Board.nsf/goto?open&id=84SRLW6E9136">View 
        all BOE school cluster assignments</a>.</p>
      ',
    ];
    
    $data = self::getData();
    
    $build[] = [
      '#markup' => vsprintf('<p><strong>%s BOE Representative:</strong> <a href="mailto:boe@hcpss.org">%s</a></p>', [
        strtoupper($data['acronym']),
        $data['boe_cluster']['representative'],
      ]),
    ];
    
    $build['#cache']['max-age'] = Cache::PERMANENT;
    
    return $build;    
  }
}
