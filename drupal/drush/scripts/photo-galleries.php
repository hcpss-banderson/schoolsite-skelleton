<?php

use Symfony\Component\DomCrawler\Crawler;
use Goutte\Client;
use Drupal\menu_item_extras\Entity\MenuItemExtrasMenuLinkContent;
use Drupal\taxonomy\Entity\Term;
use Drupal\node\Entity\Node;
use Drupal\taxonomy\TermInterface;

/**
 * drush php:script --script-path=/var/www/drupal/drush/scripts photo-galleries -- chs
 */

function delete_terms() {
  $tids = \Drupal::entityQuery('taxonomy_term')
    ->condition('vid', 'gallery_type')
    ->execute();
  
  if (!empty($tids)) {
    $terms = Term::loadMultiple($tids);
    foreach ($terms as $term) { $term->delete(); }
  }
}

function make_terms() {
  $terms = [
    'Celebration'      => 'star',
    'Clubs / Programs' => 'users',
    'Competition'      => 'award',
    'Theatre'          => 'theater-masks',
    'Art'              => 'palette',
  ];
  
  foreach ($terms as $name => $icon) {
    Term::create([
      'vid' => 'gallery_type',
      'name' => $name,
      'field_icon' => [
        'icon_name' => $icon,
        'style' => 'fas',
      ],
    ])->save();
  }
}

function get_type(string $name): TermInterface {
  $tids = \Drupal::entityQuery('taxonomy_term')
    ->condition('vid', 'gallery_type')
    ->condition('name', $name)
    ->execute();
  
  return Term::load(array_shift($tids));
}

function delete_galleries() {
  $nids = \Drupal::entityQuery('node')
    ->condition('type', 'photo_gallery')
    ->execute();
  
  if (!empty($nids)) {
    $nodes = Node::loadMultiple($nids);
    foreach ($nodes as $node) { $node->delete(); }
  }
}

delete_terms();
make_terms();
delete_galleries();

$acronym = $extra[0];
$client = new Client();
$crawler = $client->request('GET', "https://{$acronym}.hcpss.org/content-export/gallery");

$crawler->filter('.views-table > tbody > tr')->each(function (Crawler $row, $index) {
  $images = [];
  $row->filter('.export-images ol li')->each(function (Crawler $item) use (&$images) {
    $data = file_get_contents($item->filter('img')->attr('src'));
    $file = file_save_data($data, 'public://' . trim($item->filter('a')->text()));
    
    $images[] = [
      'target_id' => $file->id(),
      'alt'       => $item->filter('img')->attr('alt'),
      'title'     => '',
    ];
  });
    
  Node::create([
    'type' => 'photo_gallery',
    'uid' => 1,
    'created' => $row->filter('.export-created')->text(),
    'title' => $row->filter('.export-title')->text(),
    'body' => [
      'value' => $row->filter('.export-description')->html(),
      'format' => 'basic_html',
    ],
    'field_photos' => $images,
    'field_gallery_type' => get_type(trim($row->filter('.content-gallery-taxonomy')->text())),
  ])->save();
});
