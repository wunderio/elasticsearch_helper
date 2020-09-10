<?php

namespace Drupal\elasticsearch_helper\EventSubscriber;

use Drupal\elasticsearch_helper\Event\ElasticsearchEvents;
use Drupal\elasticsearch_helper\Event\ElasticsearchExceptionEvent;
use Drupal\elasticsearch_helper\Event\ElasticsearchOperations;
use Elasticsearch\Common\Exceptions\NoNodesAvailableException;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Logs an error when exception is thrown during Elasticsearch operation.
 */
class LoggingEventSubscriber implements EventSubscriberInterface {

  use ExceptionTrait;

  /**
   * @var \Psr\Log\LoggerInterface
   */
  protected $logger;

  /**
   * ExceptionEventSubscriber constructor.
   *
   * @param \Psr\Log\LoggerInterface $logger
   */
  public function __construct(LoggerInterface $logger) {
    $this->logger = $logger;
  }

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
    $exception = $event->getException();

    // Log the notices in expected situations.
    $request_params = $event->getRequestParameters();

    // Get exception message.
    $message = $exception->getMessage();

    // Log exception immediately if no nodes are available.
    if ($exception instanceof NoNodesAvailableException) {
      $this->logger->error($message);
    }

    if ($operation == ElasticsearchOperations::INDEX_CREATE) {
      $context = $this->getIdentifiedIndexContext($event);

      $this->logger->error('Elasticsearch index "@index" could not bet created.', $context);
    }
    elseif ($operation == ElasticsearchOperations::INDEX_TEMPLATE_CREATE) {
      $context = [
        '@index_template' => isset($request_params['name']) ? $request_params['name'] : NULL,
      ];

      $this->logger->error('Elasticsearch index template "@index_template" could not bet created.', $context);
    }
    elseif ($operation == ElasticsearchOperations::INDEX_DROP) {
      $context = $this->getIdentifiedIndexContext($event);

      $this->logger->notice('No Elasticsearch index matching "@index" could be dropped.', $context);
    }
    elseif ($operation == ElasticsearchOperations::DOCUMENT_DELETE) {
      $context = $this->getIdentifiedDocumentContext($event);
      $message = $this->isIdentifiableDocument($event)
        ? 'Could not delete document "@id" from "@index" Elasticsearch index.'
        : 'Could not delete document.';

      $this->logger->notice($message, $context);
    }
    elseif ($operation == ElasticsearchOperations::DOCUMENT_INDEX) {
      $context = $this->getIdentifiedDocumentContext($event);
      $message = $this->isIdentifiableDocument($event)
        ? 'Could not index document "@id" into "@index" Elasticsearch index.'
        : 'Could not index document.';

      $this->logger->error($message, $context);
    }
    // Log the error otherwise.
    else {
      // Do not log no nodes available error twice.
      if (!($exception instanceof NoNodesAvailableException)) {
        $this->logger->error($message);
      }
    }
  }



}
