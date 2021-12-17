<?php

namespace Drupal\hcpss_school\Plugin\Block;

use Drupal\Core\Block\BlockBase;

/**
 * Provides a Footer Block.
 *
 * @Block(
 *   id = "collage_block",
 *   admin_label = @Translation("Collage block"),
 *   category = @Translation("HCPSS"),
 * )
 */
class CollageBlock extends BlockBase {
  
  /**
   * {@inheritDoc}
   * @see \Drupal\Core\Block\BlockPluginInterface::build()
   */
  public function build() {
    $build['container'] = [
      '#type' => 'html_tag',
      '#tag' => 'div',
      '#attributes' => ['class' => 'collage'],
    ];
    
    return $build;
  }
}
