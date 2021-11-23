<?php

use Goutte\Client;
use Symfony\Component\DomCrawler\Crawler;
use Drupal\node\Entity\Node;
use Drupal\taxonomy\Entity\Term;
use Drupal\taxonomy\TermInterface;

/**
 * drush php:script --script-path=/var/www/drupal/drush/scripts scrape-news -- chs
 */

function get_or_create_term(string $name): TermInterface {
  $tids = \Drupal::entityQuery('taxonomy_term')
    ->condition('vid', 'tags')
    ->condition('name', $name)
    ->execute();
  
  if (!empty($tids)) {
    $term = Term::load(array_shift($tids));
  } else {
    $term = Term::create(['vid' => 'tags', 'name' => $name]);
    $term->save();
  }
    
  return $term;
}

function delete_all() {
  $nids = \Drupal::entityQuery('node')
    ->condition('type', 'news')
    ->execute();
  
  if (!empty($nids)) {
    foreach (Node::loadMultiple($nids) as $node) {
      $node->delete();
    }
  }
}

$acronym = $extra[0];
$client = new Client();
$url = "https://{$acronym}.hcpss.org/content-export/news_message";

delete_all();

$crawler = $client->request('GET', $url);
$crawler->filter('.views-table > tbody > tr')->each(function (Crawler $row, $index) {   
  $body = [
    'value' => $row->filter('.export-message-content')->html(),
    'format' => 'basic_html',
  ];
  
  if ($summary = $row->filter('.export-message-summary')->text()) {
    $body['summary'] = $summary;
  }
  
  $node = Node::create([
    'type' => 'news',
    'uid' => 1,
    'created' => $row->filter('.export-created')->text(),
    'title' => $row->filter('.export-title')->text(),
    'body' => $body,
  ]);
  
  if ($row->filter('.export-tags')->count()) {
    $tags = explode(',', $row->filter('.export-tags')->text());
    if (!empty($tags)) {
      foreach ($tags as $name) {
        $name = trim($name);
        if ($name) {
          $node->field_tags[] = get_or_create_term($name);
        }
      }
    }
  }
  
  $node->save();
});
