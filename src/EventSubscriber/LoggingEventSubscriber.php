<?php

namespace Drupal\elasticsearch_helper\EventSubscriber;

use Drupal\elasticsearch_helper\Event\ElasticsearchEvents;
use Drupal\elasticsearch_helper\Event\ElasticsearchOperationErrorEvent;
use Drupal\elasticsearch_helper\Event\ElasticsearchOperationResultEvent;
use Drupal\elasticsearch_helper\Event\ElasticsearchOperations;
use Elasticsearch\Common\Exceptions\NoNodesAvailableException;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Logs an error when throwable is thrown during Elasticsearch operation.
 */
class LoggingEventSubscriber implements EventSubscriberInterface {

  use OperationStatusTrait;

  /**
   * @var \Psr\Log\LoggerInterface
   */
  protected $logger;

  /**
   * LoggingEventSubscriber constructor.
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
    $events[ElasticsearchEvents::OPERATION_ERROR][]  = ['onOperationError'];
    $events[ElasticsearchEvents::OPERATION_RESULT][] = ['onOperationResult'];

    return $events;
  }

  /**
   * Logs a message if throwable is thrown during Elasticsearch operation.
   *
   * @param \Drupal\elasticsearch_helper\Event\ElasticsearchOperationErrorEvent $event
   */
  public function onOperationError(ElasticsearchOperationErrorEvent $event) {
    $operation = $event->getOperation();
    $error = $event->getError();

    // Get request parameters.
    $request_params = $event->getRequestParameters();

    // Get error message.
    $message = $error->getMessage();

    // Log error immediately if no nodes are available.
    if ($error instanceof NoNodesAvailableException) {
      $this->logger->error($message);
    }

    // Customise the message for certain expected operations.
    if ($operation == ElasticsearchOperations::INDEX_CREATE) {
      $context = $this->getIdentifiedIndexContext($event);

      $this->logger->error('Elasticsearch index "@index" could not be created.', $context);
    }
    elseif ($operation == ElasticsearchOperations::INDEX_GET) {
      $context = $this->getIdentifiedIndexContext($event);

      $this->logger->notice('Elasticsearch index "@index" could not be retrieved.', $context);
    }
    elseif ($operation == ElasticsearchOperations::INDEX_TEMPLATE_CREATE) {
      $context = [
        '@index_template' => isset($request_params['name']) ? $request_params['name'] : NULL,
      ];

      $this->logger->error('Elasticsearch index template "@index_template" could not be created.', $context);
    }
    elseif ($operation == ElasticsearchOperations::INDEX_DROP) {
      $context = $this->getIdentifiedIndexContext($event);

      $this->logger->notice('No Elasticsearch index matching "@index" could be dropped.', $context);
    }
    elseif ($operation == ElasticsearchOperations::DOCUMENT_INDEX) {
      $context = $this->getIdentifiedDocumentContext($event);
      $message = $this->isIdentifiableDocument($event)
        ? 'Could not index document "@id" into "@index" Elasticsearch index.'
        : 'Could not index document.';

      $this->logger->error($message, $context);
    }
    elseif ($operation == ElasticsearchOperations::DOCUMENT_DELETE) {
      $context = $this->getIdentifiedDocumentContext($event);
      $message = $this->isIdentifiableDocument($event)
        ? 'Could not delete document "@id" from "@index" Elasticsearch index.'
        : 'Could not delete document.';

      $this->logger->notice($message, $context);
    }
    // Log the error otherwise.
    else {
      // Do not log no-nodes-available error twice.
      if (!($error instanceof NoNodesAvailableException)) {
        $this->logger->error($message);
      }
    }
  }

  /**
   * Logs a message upon successful Elasticsearch operation.
   *
   * @param \Drupal\elasticsearch_helper\Event\ElasticsearchOperationResultEvent $event
   */
  public function onOperationResult(ElasticsearchOperationResultEvent $event) {
    $operation = $event->getOperation();
    $result = $event->getResult();

    // Customise the message for certain expected operations.
    if ($operation == ElasticsearchOperations::INDEX_CREATE && !empty($result['acknowledged'])) {
      $context = $this->getIdentifiedIndexContext($event);

      $this->logger->notice('Elasticsearch index "@index" has been created.', $context);
    }
  }

}
