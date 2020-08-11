<?php

namespace Drupal\elasticsearch_helper_example\EventSubscriber;

use Drupal\elasticsearch_helper\Event\ElasticsearchHelperEvents;
use Drupal\elasticsearch_helper\Event\ElasticsearchHelperGenericEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Class ReindexEventSubscriber
 */
class ReindexEventSubscriber implements EventSubscriberInterface {

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    $events = [];
    $events[ElasticsearchHelperEvents::REINDEX][] = ['onReindex'];

    return $events;
  }

  /**
   * Replaces the callback to dummy callback.
   *
   * @param \Drupal\elasticsearch_helper\Event\ElasticsearchHelperGenericEvent $event
   */
  public function onReindex(ElasticsearchHelperGenericEvent $event) {
    $plugin = $event->getPluginInstance();

    // Change the reindex callback for "time_based_index" index plugin.
    if ($plugin->getPluginId() == 'time_based_index') {
      $callback = &$event->getCallback();
      $callback = [$this, 'reindexNone'];
    }
  }

  /**
   * Dummy reindex callback.
   */
  public function reindexNone() {
    \Drupal::logger('elasticsearch_helper_example')->info(t('Content will not be re-indexed.'));
  }

}
