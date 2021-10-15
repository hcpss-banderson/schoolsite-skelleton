<?php

namespace Drupal\hcpss_school\EventSubscriber;

use Drupal\Core\Messenger\MessengerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\FilterResponseEvent;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Drupal\Core\Config\StorageInterface;
use Drupal\Core\Config\ConfigEvents;
use Drupal\Core\Config\MemoryStorage;
use Drupal\Core\Config\StorageTransformEvent;
use Drupal\Core\Config\StorageCopyTrait;

/**
 * HCPSS School event subscriber.
 */
class HcpssSchoolSubscriber implements EventSubscriberInterface {

  use StorageCopyTrait;
  
  /**
   * The messenger.
   *
   * @var StorageInterface
   */
  protected $sync;

  /**
   * Constructs event subscriber.
   *
   * @param StorageInterface $sync
   */
  public function __construct(StorageInterface $sync) {
    $this->sync = $sync;
  }

  /**
   * The storage is transformed for importing.
   *
   * @param \Drupal\Core\Config\StorageTransformEvent $event
   *   The event for altering configuration of the storage.
   */
  public function onImportTransform(StorageTransformEvent $event) {
    $storage = $event->getStorage();
    
    $acronym = $storage->read('hcpss_school.settings')['acronym'];
    $system_site = $storage->read('system.site');
    $data = json_decode(file_get_contents("https://api.hocoschools.org/schools/{$acronym}.json"), 1);
    $system_site['name'] = $data['full_name'];

    $storage->write('system.site', $system_site);
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    return [
      ConfigEvents::STORAGE_TRANSFORM_IMPORT => ['onImportTransform'],
    ];
  }
}
