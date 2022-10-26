<?php

namespace Drupal\elasticsearch_helper\EventSubscriber;

use Drupal\elasticsearch_helper\Event\ElasticsearchEvents;
use Drupal\elasticsearch_helper\Event\ElasticsearchOperationErrorEvent;
use Drupal\elasticsearch_helper\Event\ElasticsearchOperationRequestResultEvent;
use Drupal\elasticsearch_helper\Event\ElasticsearchOperations;
use Elastic\Transport\Exception\NoNodeAvailableException;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Logs an error when throwable is thrown during Elasticsearch operation.
 */
class LoggingEventSubscriber implements EventSubscriberInterface {

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
    $events[ElasticsearchEvents::OPERATION_ERROR][] = ['onOperationError'];
    $events[ElasticsearchEvents::OPERATION_REQUEST_RESULT][] = ['onRequestResult'];

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

    // Get request wrapper.
    $request_wrapper = $event->getRequestWrapper();

    // Get request parameters.
    $callback_params = $event->getCallbackParameters();

    // Get error message.
    $error_message = $error->getMessage();

    // Log error immediately if no nodes are available.
    if ($error instanceof NoNodeAvailableException) {
      $this->logger->error($error_message);
    }

    // Customise the message for certain expected operations.
    if ($operation == ElasticsearchOperations::INDEX_CREATE) {
      $t_args = $event->getMessageContextArguments();

      $this->logger->error('Elasticsearch index "@index" could not be created due to the following error: @error.', $t_args);
    }
    elseif ($operation == ElasticsearchOperations::INDEX_TEMPLATE_CREATE) {
      $t_args = [
        '@error' => $event->getError()->getMessage(),
        '@index_template' => isset($callback_params[0]['name']) ? $callback_params[0]['name'] : NULL,
      ];

      $this->logger->error('Elasticsearch index template "@index_template" could not be created due to the following error: @error.', $t_args);
    }
    elseif ($operation == ElasticsearchOperations::INDEX_GET) {
      $t_args = $event->getMessageContextArguments();

      $this->logger->notice('Elasticsearch index "@index" could not be retrieved due to the following error: @error.', $t_args);
    }
    elseif ($operation == ElasticsearchOperations::INDEX_DROP) {
      $t_args = $event->getMessageContextArguments();

      $this->logger->notice('Elasticsearch index matching "@index" could not be deleted due to the following error: @error. ', $t_args);
    }
    elseif ($operation == ElasticsearchOperations::INDEX_TEMPLATE_DROP) {
      $t_args = [
        '@error' => $event->getError()->getMessage(),
        '@index_template' => isset($callback_params[0]['name']) ? $callback_params[0]['name'] : NULL,
      ];

      $this->logger->notice('Elasticsearch index template matching "@index_template" could not be deleted due to the following error: @error. ', $t_args);
    }
    elseif ($operation == ElasticsearchOperations::DOCUMENT_INDEX) {
      $t_args = $event->getMessageContextArguments();

      $error_message = $request_wrapper && $request_wrapper->getDocumentId()
        ? 'Could not index document "@id" into "@index" Elasticsearch index due to the following error: @error'
        : '@error';

      $this->logger->error($error_message, $t_args);
    }
    elseif ($operation == ElasticsearchOperations::DOCUMENT_DELETE) {
      $t_args = $event->getMessageContextArguments();

      $error_message = $request_wrapper && $request_wrapper->getDocumentId()
        ? 'Could not delete document "@id" from "@index" Elasticsearch index due to the following error: @error'
        : '@error';

      $this->logger->notice($error_message, $t_args);
    }
    // Log the error otherwise.
    else {
      // Do not log no-nodes-available error twice.
      if (!($error instanceof NoNodeAvailableException)) {
        $this->logger->error($error_message);
      }
    }
  }

  /**
   * Logs a message upon successful Elasticsearch operation.
   *
   * @param \Drupal\elasticsearch_helper\Event\ElasticsearchOperationRequestResultEvent $event
   */
  public function onRequestResult(ElasticsearchOperationRequestResultEvent $event) {
    // Get request result body.
    $result = $event->getResult()->getResultBody();

    // Get operation.
    $operation = $event->getRequestWrapper()->getOperation();

    // Customise the message for certain expected operations.
    if ($operation == ElasticsearchOperations::INDEX_CREATE && !empty($result['acknowledged'])) {
      $t_args = $event->getMessageContextArguments();

      $this->logger->notice('Elasticsearch index "@index" has been created.', $t_args);
    }
    elseif ($operation == ElasticsearchOperations::INDEX_TEMPLATE_DROP && !empty($result['acknowledged'])) {
      $t_args = [
        '@index_template' => isset($callback_params[0]['name']) ? $callback_params[0]['name'] : NULL,
      ];

      $this->logger->notice('Elasticsearch index template "@index_template" has been deleted.', $t_args);
    }
  }

}
