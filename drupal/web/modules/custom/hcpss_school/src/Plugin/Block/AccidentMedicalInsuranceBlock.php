<?php

namespace Drupal\hcpss_school\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Url;

/**
 * Provides a block with resources for athletic participation.
 *
 * @Block(
 *   id = "accident_medical_insurance_block",
 *   admin_label = @Translation("Accident and Medical Insurance"),
 *   category = @Translation("HCPSS"),
 * )
 */
class AccidentMedicalInsuranceBlock extends BlockBase {
  
  /**
   * {@inheritDoc}
   * @see \Drupal\Core\Block\BlockPluginInterface::build()
   */
  public function build() {
    $build = [];
    
    $build[] = [
      '#type' => 'html_tag',
      '#tag' => 'h3',
      '#value' => 'Accident Insurance',
    ];
    
    $build[] = [
      '#type' => 'html_tag',
      '#tag' => 'p',
      '#value' => '
        The school system does not carry medical insurance for accidents that 
        occur at school, including non-sport related injuries, such as trips and 
        falls, physical education injuries, and recess/playground injuries. 
        Thus, parents are encouraged to enroll students in the accident 
        insurance program offered through HCPSS.
      ',
    ];
    
    $build[] = [
      '#title' => 'Learn more about how to obtain accident insurance →',
      '#type' => 'link',
      '#url' => Url::fromUri('https://www.hcpss.org/about-us/handbook/wellness/#accident'),
    ];
    
    $build[] = [
      '#type' => 'html_tag',
      '#tag' => 'h3',
      '#value' => 'Medical Insurance',
    ];
    
    $build[] = [
      '#type' => 'html_tag',
      '#tag' => 'p',
      '#value' => '
        The Maryland Children’s Health Insurance Program (MCHIP) is available 
        for families, pregnant women, and children in need of medical insurance. 
        Applications are available through the school health assistant or by 
        contacting the Howard County Health Department at 410-313-7500.
      ',
    ];
    
    $build[] = [
      '#title' => 'Find out more about qualifying for MCHIP →',
      '#type' => 'link',
      '#url' => Url::fromUri('https://www.hcpss.org/about-us/handbook/wellness/#medical'),
    ];
    
    return $build;
  }
}
