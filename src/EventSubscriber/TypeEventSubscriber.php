<?php

namespace Drupal\elasticsearch_helper\EventSubscriber;

use Drupal\elasticsearch_helper\ElasticsearchClientVersion;
use Drupal\elasticsearch_helper\Event\ElasticsearchEvents;
use Drupal\elasticsearch_helper\Event\ElasticsearchOperationRequestEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class TypeEventSubscriber implements EventSubscriberInterface {

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    $events = [];
    $events[ElasticsearchEvents::OPERATION_REQUEST][] = ['onOperationRequest'];

    return $events;
  }

  /**
   * Removes "type" parameter from the request if Elasticsearch server is >= 7.
   *
   * @param \Drupal\elasticsearch_helper\Event\ElasticsearchOperationRequestEvent $event
   */
  public function onOperationRequest(ElasticsearchOperationRequestEvent $event) {
    if (ElasticsearchClientVersion::getMajorVersion() >= 7) {
      $callback_parameters = &$event->getCallbackParameters();

      if (isset($callback_parameters[0]['type'])) {
        unset($callback_parameters[0]['type']);
      }
    }
  }

}
