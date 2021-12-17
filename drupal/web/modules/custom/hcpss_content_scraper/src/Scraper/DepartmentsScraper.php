<?php

namespace Drupal\hcpss_content_scraper\Scraper;

use Drupal\hcpss_content_scraper\Service\ScraperService;
use Drupal\paragraphs\Entity\Paragraph;
use Symfony\Component\DomCrawler\Crawler;
use Drupal\fragments\Entity\Fragment;
use Drupal\fragments\Entity\FragmentInterface;
use Drupal\hcpss_content_scraper\ScraperInterface;
use Drupal\hcpss_content_scraper\ScraperBase;

class DepartmentsScraper extends ScraperBase implements ScraperInterface {
  
  /**
   * {@inheritDoc}
   * @see \Drupal\hcpss_content_scraper\ScraperInterface::getPath()
   */
  protected function getUrl(): string {
    return "https://{$this->acronym}.hcpss.org/content-export/department";
  }
  
  /**
   * Scrape staff members from the given URL.
   * 
   * @param string $url
   * @return Paragraph[]
   */
  protected function scrapeStaffMembers(string $url): array {
    $paragraphs = [];
    $scraper = ScraperService::createFromUrl($url);
    $selector = '.field-name-field-department-staff';
    
    if ($scraper->crawl()->filter($selector)->count()) {
      $scraper->crawl()->filter($selector)->each(function (Crawler $div) use (&$paragraphs) {
        $row = new ScraperService($div);
        
        $fname = $row->safeText('.field-name-field-staff-first-name');
        $lname = $row->safeText('.field-name-field-staff-last-name');
        $job   = $row->safeText('.field-name-field-staff-job-title');
        $email = $row->safeText('.field-name-field-staff-email');
        
        $paragraph = Paragraph::create([
          'type' => 'staff_member',
          'field_name' => "$fname $lname",
          'field_job_title' => $job,
          'field_email' => $email,
        ]);
        
        if ($div->filter('.field-name-field-staff-website a')->count()) {
          $href = $div->filter('.field-name-field-staff-website a')->attr('href');
          $paragraph->field_link[] = ['title' => 'Website', 'uri' => $href];
        }
        
        $paragraph->save();
        $paragraphs[] = $paragraph;
      });
    }
   
    return $paragraphs;
  }
  
  /**
   * Scrape the single department from the URL.
   * 
   * @param string $url
   * @return FragmentInterface
   */
  protected function scrapeDepartment(string $url): FragmentInterface {
    $paragraphs = $this->scrapeStaffMembers($url);
    $scraper = ScraperService::createFromUrl($url);
    
    $fragment = Fragment::create([
      'type' => 'department',
      'uid' => 1,
      'title' => $scraper->crawl()->filter('.node h2')->text(),
      'field_staff_members' => $paragraphs,
    ]);
    
    $fragment->save();
    
    return $fragment;
  }
  
  /**
   * {@inheritDoc}
   * @see \Drupal\hcpss_content_scraper\ScraperInterface::scrape()
   */
  public function scrape(): array {    
    $num_departments = $num_members = 0;
    
    $rows = ScraperService::scrape($this->getUrl());
    print_r($rows);
    foreach ($rows as $row) {
      $department_url = "https://{$this->acronym}.hcpss.org{$row['path']}";
      $department = $this->scrapeDepartment($department_url);
      
      $num_departments++;
      $num_members += $department->get('field_staff_members')->count();
    }
    
    return [
      'fragment' => ['department' => $num_departments],
      'paragraph' => ['staff_member' => $num_members],
    ];
  }
}
