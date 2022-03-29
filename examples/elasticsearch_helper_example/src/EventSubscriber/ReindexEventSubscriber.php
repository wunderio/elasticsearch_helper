<?php

namespace Drupal\elasticsearch_helper_example\EventSubscriber;

use Drupal\elasticsearch_helper\Event\ElasticsearchHelperEvents;
use Drupal\elasticsearch_helper\Event\ElasticsearchHelperCallbackEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Class ReindexEventSubscriber
 */
class ReindexEventSubscriber implements EventSubscriberInterface {

  /**
   * @var string
   */
  protected $ignoredPluginId = 'time_based_index';

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
   * @param \Drupal\elasticsearch_helper\Event\ElasticsearchHelperCallbackEvent $event
   */
  public function onReindex(ElasticsearchHelperCallbackEvent $event) {
    $plugin = $event->getPluginInstance();

    // Change the reindex callback for "time_based_index" index plugin.
    if ($plugin->getPluginId() == $this->ignoredPluginId) {
      $callback = &$event->getCallback();
      $callback = [$this, 'reindexNone'];
    }
  }

  /**
   * Dummy reindex callback.
   */
  public function reindexNone() {
    $t_args = ['@plugin_id' => $this->ignoredPluginId];
    $message = t('Content will not be re-indexed for "@plugin_id" index plugin.', $t_args);
    \Drupal::logger('elasticsearch_helper_example')->notice($message);
  }

}
