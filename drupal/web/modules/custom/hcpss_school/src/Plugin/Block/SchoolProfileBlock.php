<?php

namespace Drupal\hcpss_school\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\hcpss_school\ApiDataAwareTrait;
use Drupal\Core\Cache\Cache;
use Drupal\Core\Url;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Access\AccessResult;

/**
 * Provides a School Profile Block.
 *
 * @Block(
 *   id = "school_profile_block",
 *   admin_label = @Translation("School Profile"),
 *   category = @Translation("HCPSS"),
 * )
 */
class SchoolProfileBlock extends BlockBase {
  
  use ApiDataAwareTrait;
  
  /**
   * {@inheritDoc}
   * @see \Drupal\Core\Block\BlockPluginInterface::build()
   */
  public function build() {
    $build = [];
    
    $data = self::getData();
    
    $build[] = [
      '#type' => 'html_tag',
      '#tag' => 'p',
      '#value' => '
        Fast facts about ' . $data['full_name'] . ' listing special programs, 
        total enrollment, accomplishments and more.
      ',
    ];
    
    $build[] = [
      '#type' => 'link',
      '#title' => 'View the School Profile for' . $data['full_name'],
      '#url' => Url::fromUri($data['profile']),
      '#prefix' => '<p>',
      '#suffix' => '</p>',
    ];
    
    $build['#cache']['max-age'] = Cache::PERMANENT;
    
    return $build;    
  }
  
  /**
   * @param AccountInterface $account
   * @return \Drupal\Core\Access\AccessResultForbidden|\Drupal\Core\Access\AccessResultNeutral
   */
  protected function blockAccess(AccountInterface $account) {
    if (!self::getData()['profile']) {
      return AccessResult::forbidden('No school profile');
    }
    
    return AccessResult::neutral();
  }
}
