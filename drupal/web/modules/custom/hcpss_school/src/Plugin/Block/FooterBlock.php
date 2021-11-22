<?php

namespace Drupal\hcpss_school\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Component\Serialization\Yaml;
use Drupal\Component\Serialization\Json;
use Drupal\Core\Url;
use Drupal\Core\Cache\Cache;
use Drupal\hcpss_school\ApiDataAware;

/**
 * Provides a Footer Block.
 *
 * @Block(
 *   id = "footer_block",
 *   admin_label = @Translation("Footer block"),
 *   category = @Translation("HCPSS"),
 * )
 */
class FooterBlock extends BlockBase {
  
  use ApiDataAware;
  
  /**
   * Build the static resources.
   * 
   * @return array
   */
  private function buildResources(): array {
    $path = drupal_get_path('module', 'hcpss_school') . '/data/footer-resources.yml';
    $resources = Yaml::decode(file_get_contents($path));
    
    $build = [];
    foreach($resources as $name => $resource) {
      $build[$name] = [
        '#type' => 'container',
      ];
      
      $build[$name]['title'] = [
        '#type' => 'html_tag',
        '#tag' => 'h4',
        '#value' => $this->t($resource['title']),
      ];
      
      $build[$name]['description'] = [
        '#type' => 'html_tag',
        '#tag' => 'p',
        '#value' => $this->t($resource['description']),
      ];
      
      $build[$name]['link'] = [
        '#type' => 'link',
        '#attributes' => ['class' => 'button'],
        '#title' => $this->t($resource['link']['label']),
        '#url' => Url::fromUri($resource['link']['uri']),
        '#prefix' => '<p>',
        '#suffix' => '</p>'
      ];
    }
    
    return $build;
  }
  
  /**
   * Build the address block.
   * 
   * @return array
   */
  private function buildAddress(): array {
    $school_info = self::getData();
    $build = [];
    
    $build['address'] = [
      '#type' => 'container',
      '#attributes' => ['class' => 'address'],
    ];
    
    $build['address']['address'] = [
      '#type' => 'markup',
      '#markup' => vsprintf('
        <address>
          <h4>%s</h4>
          %s<br>
          %s, MD %s<br>
          <abbr title="Phone">P:</abbr> %s<br>
          <abbr title="Fax">F:</abbr> %s
        </address>
      ', [
        $school_info['full_name'],
        $school_info['address']['street'],
        $school_info['address']['city'],
        $school_info['address']['postal_code'],
        $school_info['contact']['phone'],
        $school_info['contact']['fax'],
      ]),
    ];
    
    $build['address']['times'] = [
      '#type' => 'html_tag',
      '#tag' => 'p',
      '#value' => $this->t('School Start and Dismissal Times: @open - @close', [
        '@open' => $school_info['hours']['open'],
        '@close' => $school_info['hours']['close'],
      ]),
    ];
    
    $build['address']['links'] = [
      '#type' => 'html_tag',
      '#tag' => 'p',
    ];
    
    if ($school_info['walk_area']) {
      $build['address']['links'][] = [
        '#type' => 'link',
        '#title' => $this->t('Walk Area Map'),
        '#url' => Url::fromUri($school_info['walk_area']),
      ];
    }
    
    if ($school_info['profile']) {
      $build['address']['links'][] = [
        '#type' => 'link',
        '#title' => $this->t('School Profile'),
        '#url' => Url::fromUri($school_info['profile']),
      ];
    }
    
    return $build;
  }
  
  /**
   * Build the logo.
   * 
   * @return array
   */
  private function buildLogo(): array {
    $build = [
      '#type' => 'container',
      '#attributes' => ['class' => 'footer__hcpss-logo'],
    ];
    
    $build['link'] = [
      '#markup' => '
        <a href="https://www.hcpss.org/" target="_blank">
          <img alt="HCPSS Logo." src="https://www.hcpss.org/f/mrb/base/hcpss-logo-outlined.png">
        </a>
      '
    ];
    
    return $build;
  }
  
  /**
   * Build the footer.
   * 
   * @return array
   */
  public function buildFooter(): array {
    $build = ['#type' => 'container'];
    $build['hcpss'] = [
      '#type' => 'html_tag',
      '#tag' => 'p',
      '#value' => $this->t('Part of the Howard County Public School System'),
      '#prefix' => '<hr>',
    ];
    
    $build['lang'] = ['#type' => 'container'];
    $build['lang']['es'] = [
      '#type' => 'link',
      '#title' => 'Servicios de Idiomas',
      '#url' => Url::fromUri('https://www.hcpss.org/es/'),
    ];
    
    $build['lang']['zh'] = [
      '#type' => 'link',
      '#title' => '语言服务',
      '#url' => Url::fromUri('https://www.hcpss.org/zh/'),
      '#prefix' => ' | ',
    ];
    
    $build['lang']['ko'] = [
      '#type' => 'link',
      '#title' => '언어 서비스',
      '#url' => Url::fromUri('https://www.hcpss.org/ko/'),
      '#prefix' => ' | ',
    ];
    
    $build['lang']['cnh'] = [
      '#type' => 'link',
      '#title' => 'Holhlei Riantuanmihna',
      '#url' => Url::fromUri('https://www.hcpss.org/cnh/'),
      '#prefix' => ' | ',
    ];
    
    return $build;
  }
  
  /**
   * {@inheritDoc}
   * @see \Drupal\Core\Block\BlockPluginInterface::build()
   */
  public function build() {
    $build['container'] = [
      '#type' => 'container', 
      '#attributes' => ['class' => 'container'],
    ];
    
    $build['container']['logo']      = $this->buildLogo();
    
    $build['container']['resources'] = [
      '#type' => 'container',
      '#attributes' => ['class' => 'resources']
    ];
    
    $build['container']['resources'] += $this->buildResources();
    $build['container']['resources'] += $this->buildAddress();
    $build['container']['footer']    = $this->buildFooter();
    
    $build['#cache']['max-age'] = Cache::PERMANENT;
    
    return $build;
  }
}
