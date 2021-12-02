<?php

namespace Drupal\hcpss_ieq\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Component\Utility\UrlHelper;
use GuzzleHttp\Exception\RequestException;
use Drupal\Component\Serialization\Json;
use Drupal\Core\Render\Markup;
use Drupal\hcpss_ieq\Service\IeqService;

/**
 * Returns responses for IEQ routes.
 */
class IeqController extends ControllerBase {

  /**
   * @var IeqService
   */
  private $ieqService;
  
  public function __construct() {
    $acronym = \Drupal::config('hcpss_school.settings')->get('acronym');
    $this->ieqService = new IeqService('https://ieq.hcpss.org/jsonapi', $acronym);
  }
  
  /**
   * Builds the response.
   */
  public function build() {
    $acronym = \Drupal::config('hcpss_school.settings')->get('acronym');
    
    $build[] = [
      '#type' => 'html_tag',
      '#tag' => 'p',
      '#value' => '
        HCPSS recognizes the importance of providing a healthy learning 
        environment for all students and staff members. In an effort to identify 
        and correct conditions that may have an impact on air quality, HCPSS has 
        established an Indoor Environmental Quality (IEQ) program that 
        proactively maintains proper indoor environments and responds to 
        community members\' concerns.
      ',
    ];
    
    $build[] = ['#markup' => '
      <p>The IEQ data provided below comes directly from the 
      <a href="https://ieq.hcpss.org/">HCPSS IEQ site</a>.</p>
    '];
    
    $build[] = [
      '#type' => 'html_tag', 
      '#tag' => 'h2', 
      '#value' => 'Submit an IEQ Concern',
      '#prefix' => '<hr>',
    ];
    
    $build[] = ['#markup' => '
      <p>Complete the online 
      <a href="https://ieq.hcpss.org/concern/new">IEQ Concern form</a> to submit 
      a concern about the indoor environmental quality of a HCPSS building or 
      facility. The Office of the Environment addresses each concern by 
      contacting the individual and performing an environmental assessment of 
      the reported area.</p>
    '];
    
    $build[] = [
      '#type' => 'html_tag',
      '#tag' => 'h2',
      '#value' => 'Recently Published Concerns',
    ];
    
    $build[] = $this->concernTable($acronym);
    
    $build[] = ['#markup' => '
      A full listing of published concerns is available on this 
      <a href="https://ieq.hcpss.org/' . $acronym . '">school\'s page on the 
      HCPSS IEQ site</a>. 
    '];
    
    $build[] = [
      '#type' => 'html_tag',
      '#tag' => 'h2',
      '#value' => 'Scheduled Walk-Throughs',
      '#prefix' => '<hr>',
    ];
    
    $build[] = $this->walkthroughList();
    
    $build[] = [
      '#type' => 'html_tag',
      '#tag' => 'p',
      '#value' => '
        The IEQ program includes system-wide scheduled and standardized 
        walk-through reviews of each HCPSS school, conducted twice a year. The 
        process is based off the Environmental Protection Agency’s (EPA) Indoor 
        Air Quality recommendations for schools. One walkthrough is conducted by 
        a school-based IEQ team of parents, staff, students, and community 
        members, trained by the HCPSS Office of the Environment, to identify and 
        report potential indoor environmental issues. The second walkthrough is 
        performed by an HCPSS industrial hygienist, who assesses each school 
        building, such as its mechanical equipment, areas above drop-ceilings, 
        and the exterior.
      ',
    ];
    
    $build[] = [
      '#type' => 'html_tag',
      '#tag' => 'h2',
      '#value' => 'IEQ Information From Previous School Years',
      '#prefix' => '<hr>',
    ];
    
    $build[] = ['#markup' => '
      <p>All previously published IEQ concerns, reports from external contractors, 
      and school walk-through reports for previous school years are archived on 
      the <a href="https://ieq.hcpss.org/' . $acronym . '-archive">IEQ Report 
      Archive<a/>.</p>
    '];
    
    $build[] = [
      '#type' => 'html_tag',
      '#tag' => 'h2',
      '#value' => 'Water Quality Reports',
      '#prefix' => '<hr>',
    ];
    
    $build[] = ['#markup' => '
      <p>In compliance with state regulations, HCPSS is testing all schools for 
      the presence of lead in drinking water. Details on the testing schedule, 
      procedures, etc., as well as test results and reports for individual 
      schools can be found on the 
      <a href="https://www.hcpss.org/schools/water-quality-reports/">HCPSS 
      website</a>.</p>
    '];

    return $build;
  }
  
  /**
   * Render walkthrough data.
   * 
   * @param string $acronym
   */
  private function walkthroughList(): array {
    $walkthroughs       = $this->ieqService->fetchWalkthroughs();
    $hvacAssessments    = $this->ieqService->fetchHvacAssessments();
    $generalAssessments = $this->ieqService->fetchGeneralAssessments();
    
    $assessments = array_merge(
      $walkthroughs['data'], 
      $hvacAssessments['data'],
      $generalAssessments['data']  
    );
    
    usort($assessments, function ($a, $b) {
      $a_date = $a['attributes']['field_date'];
      $b_date = $b['attributes']['field_date'];
      if ($a_date == $b_date) {
        return 0;
      }
      return ($a_date < $b_date) ? -1 : 1;
    });
    
    $assessments = array_slice($assessments, 0, 5);
    
    $list = [
      '#theme' => 'item_list',
      '#type' => 'ul',
      '#title' => 'Scheduled Walk-Throughs',
      '#items' => [],
    ];
    
    foreach ($assessments as $assessment) {
      $attributes = $assessment['attributes'];
            
      $timestamp = !empty($attributes['field_date']) 
        ? \DateTimeImmutable
          ::createFromFormat('Y-m-d', $attributes['field_date'])
          ->getTimestamp()
        : (!empty($attributes['published_at'])
          ? strtotime($attributes['published_at'])
          : strtotime($attributes['created']));
      
      $list['#items'][] = Markup::create(vsprintf('
          <a href="https://ieq.hcpss.org/%s/%s">%s</a><br>
          <small>Tracking Number: %s</small>
        ', [
          $assessment['type'] == 'node--walkthrough' ? 'walkthrough' : 'assessment',
          strtolower($attributes['field_tracking_number']),
          date('F jS, Y', $timestamp),
          $attributes['field_tracking_number'],
        ]
      ));
    }
    
    return $list;
  }
  
  /**
   * Generate the concern table. 
   */
  private function concernTable(string $acronym): array {
    $table = ['#markup' => '
      <p><strong>There are no published IEQ concerns for this school at this
      time.</strong> Any open concerns submitted after August 20, 2018 are
      progressing through our investigative and assessment process.</p>
    '];
    
    $data = $this->ieqService->fetchConcerns();
    
    $table = [
      '#type' => 'table',
      '#header' => [
        ['data' => 'Date',     'scope' => 'col'],
        ['data' => 'Location', 'scope' => 'col'],
        ['data' => 'Concern',  'scope' => 'col'],
        ['data' => 'Closed',   'scope' => 'col'],
      ],
      '#rows' => [],
    ];
    
    foreach ($data['data'] as $item) {      
      $table['#rows'][] = [
        [
          'data' => Markup::create(vsprintf('
              <a href="https://ieq.hcpss.org/concerns/%s">%s</a><br>
              <small>Tracking Number: %s</small>
            ', [
              strtolower($item['attributes']['field_tracking_number']),
              date('F jS, Y', strtotime($item['attributes']['field_date_of_report'])),
              $item['attributes']['field_tracking_number'],
            ]
          )),
          'header' => TRUE,
          'scope' => 'row',
        ],
        ['data' => $item['attributes']['field_location']],
        ['data' => $item['attributes']['field_concern']],
        ['data' => $item['attributes']['field_closed'] ? 'Yes' : 'No'],
      ];
    }
    
    return $table;
  }
}
