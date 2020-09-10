<?php

namespace Drupal\elasticsearch_helper\EventSubscriber;

use Drupal\Core\Messenger\MessengerTrait;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\elasticsearch_helper\Event\ElasticsearchEvents;
use Drupal\elasticsearch_helper\Event\ElasticsearchExceptionEvent;
use Drupal\elasticsearch_helper\Event\ElasticsearchOperations;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Displays a message when exception is thrown during Elasticsearch operation.
 */
class MessagingEventSubscriber implements EventSubscriberInterface {

  use MessengerTrait;
  use StringTranslationTrait;
  use ExceptionTrait;

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    $events = [];
    $events[ElasticsearchEvents::EXCEPTION][] = ['onException'];

    return $events;
  }

  /**
   * Logs an error when exception is thrown during Elasticsearch operation.
   *
   * @param \Drupal\elasticsearch_helper\Event\ElasticsearchExceptionEvent $event
   */
  public function onException(ElasticsearchExceptionEvent $event) {
    $operation = $event->getOperation();

    if ($operation == ElasticsearchOperations::DOCUMENT_INDEX) {
      $context = $this->getIdentifiedDocumentContext($event);
      $message = $this->isIdentifiableDocument($event)
        ? 'Could not index document "@id" into "@index" Elasticsearch index.'
        : 'Could not index document.';

      $this->messenger()->addError($this->t($message, $context));
    }
  }

}
