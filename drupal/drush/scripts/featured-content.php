<?php

use Drupal\entityqueue\Entity\EntitySubqueue;
use Symfony\Component\DomCrawler\Crawler;
use Drupal\node\Entity\Node;
use Goutte\Client;

/**
 * drush php:script --script-path=/var/www/drupal/drush/scripts featured-content -- chs
 */

$acronym = $extra[0];
$legacyClient = new Client();
$legacyCrawler = $legacyClient->request('GET', "https://{$acronym}.hcpss.org/");

$queue = EntitySubqueue::load('featured_content');
$legacyCrawler->filter('a.grid-item > h1')->each(function (Crawler $header, $index) use ($queue) {
  $nids = \Drupal::entityQuery('node')
    ->condition('type', 'news')
    ->condition('title', trim($header->text()))
    ->execute();
  
  if (!empty($nids)) {
    $node = Node::load(array_shift($nids));
    $queue->addItem($node);
  }
});
$queue->save();
