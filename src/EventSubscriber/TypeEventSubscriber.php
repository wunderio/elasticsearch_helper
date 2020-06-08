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
      $callable_parameters = &$event->getCallableParameters();

      if (isset($callable_parameters[0]['type'])) {
        unset($callable_parameters[0]['type']);
      }
    }
  }

}
