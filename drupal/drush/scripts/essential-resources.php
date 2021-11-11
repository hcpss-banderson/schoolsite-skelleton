<?php

use Symfony\Component\DomCrawler\Crawler;
use Goutte\Client;
use Drupal\menu_item_extras\Entity\MenuItemExtrasMenuLinkContent;

/**
 * drush php:script --script-path=/var/www/drupal/drush/scripts essential-resources -- chs
 */

function delete_all() {
  $mids = \Drupal::entityQuery('menu_link_content')
    ->condition('menu_name', 'essential-apps-resources')
    ->execute();
  
  $controller = \Drupal::entityTypeManager()->getStorage('menu_link_content');
  $entities = $controller->loadMultiple($mids);
  $controller->delete($entities);
}

delete_all();

$acronym = $extra[0];
$client = new Client();
$crawler = $client->request('GET', "https://{$acronym}.hcpss.org/");

$crawler->filter('.bullets .bullet-content')->each(function (Crawler $column, $index) {
  $parent = MenuItemExtrasMenuLinkContent::create([
    'bundle'    => 'essential-apps-resources',
    'enabled'   => 1,
    'title'     => $column->filter('h2')->text(),
    'link'      => ['uri' => 'route:<nolink>'],
    'menu_name' => 'essential-apps-resources',
    'weight'    => $index, 
  ]);
  
  $parent->save();
  
  $column->filter('p')->each(function (Crawler $p, $weight) use ($parent) {
    $body = $p->html();
    $body = explode('<br>', $body)[1];    
    
    MenuItemExtrasMenuLinkContent::create([
      'bundle'     => 'essential-apps-resources',
      'enabled'    => 1,
      'title'      => $p->filter('a')->text(),
      'link'       => ['uri' => $p->filter('a')->attr('href')],
      'weight'     => $weight,
      'field_body' => trim($body),
      'menu_name'  => 'essential-apps-resources',
      'parent'     => 'menu_link_content:' . $parent->uuid(),
    ])->save();
  });
});
