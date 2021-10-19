<?php

use Goutte\Client;
use Drupal\paragraphs\Entity\Paragraph;
use Drupal\fragments\Entity\Fragment;
use Symfony\Component\DomCrawler\Crawler;
use Drupal\entityqueue\Entity\EntitySubqueue;
use Drupal\node\Entity\Node;
use Drupal\taxonomy\Plugin\views\wizard\TaxonomyTerm;
use Drupal\taxonomy\Entity\Term;

/**
 * drush php:script --script-path=/var/www/drupal/drush/scripts scrape-news -- chs
 */

function crawlNewsPage(Crawler $crawler, $acronym) {
  $crawler->filter('.news-message')->each(function (Crawler $message) use ($acronym) {
    $time = DateTimeImmutable::createFromFormat(
      'D, m/d/Y - g:ia', 
      $message->filter('.news-message-detail')->text(),
      new DateTimeZone('America/New_York')
    );
    
    $node = Node::create([
      'type' => 'news',
      'uid' => 1,
      'created' => $time->getTimestamp(),
    ]);
    
    $uri = $message->filter('.node-readmore a')->link()->getUri();
    $newsCrawler = (new Client())->request('GET', $uri);
    
    $tags = [];
    $newsCrawler->filter('.field-name-field-tags a')->each(function (Crawler $tag) use ($node, &$tags) {
      $name = $tag->text();
      
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
      
      $tags[] = $term;
    });
      
      $node->setTitle($newsCrawler->filter('.content h2')->text());
      $node->body = [
        'value' => $newsCrawler->filter('.field-name-field-news-message-content')->html(),
        'format' => 'basic_html',
      ];
      
      if (!empty($tags)) {
        $node->field_tags = $tags;
      }
      
      $node->save();
  });
}

$acronym = $extra[0];
$client = new Client();
$url = "https://{$acronym}.hcpss.org/news";

do {
  $crawler = $client->request('GET', $url);
  crawlNewsPage($crawler, $acronym);
  
  if ($crawler->filter('.pager-next a')->count()) {
    $url = $crawler->filter('.pager-next a')->link()->getUri();
  } else {
    $url = false;
  }
} while($url);
