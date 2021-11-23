<?php

namespace Drupal\hcpss_school\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\hcpss_school\ApiDataAwareTrait;
use Drupal\Core\Cache\Cache;
use Drupal\Core\Url;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Access\AccessResult;

/**
 * Provides a MSDE School Report Card Block.
 *
 * @Block(
 *   id = "school_report_card_block",
 *   admin_label = @Translation("MSDE School Report Card"),
 *   category = @Translation("HCPSS"),
 * )
 */
class SchoolReportCardBlock extends BlockBase {
  
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
        The Maryland State Department of Education (MSDE) School Report Card 
        reflects overall school performance using a combination of academic and 
        school quality indicators.
      ',
    ];
    
    $build[] = [
      '#type' => 'link',
      '#title' => 'View the MSDE School Report Card for' . $data['full_name'],
      '#url' => Url::fromUri($data['msde_report']),
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
    if (!self::getData()['msde_report']) {
      return AccessResult::forbidden('No MSDE report');
    }
    
    return AccessResult::neutral();
  }
}
