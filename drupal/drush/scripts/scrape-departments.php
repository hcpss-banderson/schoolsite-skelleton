<?php

use Goutte\Client;
use Drupal\paragraphs\Entity\Paragraph;
use Drupal\fragments\Entity\Fragment;
use Symfony\Component\DomCrawler\Crawler;
use Drupal\entityqueue\Entity\EntitySubqueue;

/**
 * drush php:script --script-path=/var/www/drupal/drush/scripts scrape-departments -- chs
 */

$acronym = $extra[0];

$legacyClient = new Client();
$legacyCrawler = $legacyClient->request('GET', "https://{$acronym}.hcpss.org/school-staff");

$queue = EntitySubqueue::load('staff_list');

$legacyCrawler->filter('.node-department')->each(function (Crawler $dep_element, $dep_i) use ($queue) {
    $paragraphs = [];
    $dep_element->filter('.school-staff-member')->each(function (Crawler $staff_element, $staff_i) use (&$paragraphs) {
        $prefix = 'field-name-field-staff';
        $name = vsprintf('%s %s', [
            $staff_element->filter(".{$prefix}-first-name")->text(),
            $staff_element->filter(".{$prefix}-last-name")->text(),
        ]);
        
        $title = $staff_element->filter(".{$prefix}-job-title")->text('');
        $email = $staff_element->filter(".{$prefix}-email a")->text('');
        
        $paragraph = Paragraph::create([
            'type' => 'staff_member',
            'field_name' => $name,
            'field_job_title' => $title,
            'field_email' => $email,
        ]);
        
        try {
          $link = $staff_element->filter('.field-name-field-staff-website a');
          $paragraph->field_website = [
            'uri' => $link->attr('href'),
            'title' => $link->text(),
            'options' => [],
          ];
        } catch (Exception $e) {}
        
        $paragraph->save();
        $paragraphs[] = $paragraph;
    });
    
    $department = Fragment::create([
        'type' => 'department',
        'user_id' => 1,
        'title' => $dep_element->filter('h2')->text(),
        'field_staff_members' => $paragraphs,
    ]);
    $department->save();
    $queue->addItem($department);
});

$queue->save();
