<?php

namespace Drupal\elasticsearch_helper\EventSubscriber;

use Drupal\elasticsearch_helper\Event\ElasticsearchOperationExceptionEvent;

/**
 * Provides common methods for data retrieval from exception event.
 */
trait ExceptionTrait {

  /**
   * Returns TRUE id exception is related to an identifiable document.
   *
   * @param \Drupal\elasticsearch_helper\Event\ElasticsearchOperationExceptionEvent $event
   *
   * @return bool
   */
  protected function isIdentifiableDocument(ElasticsearchOperationExceptionEvent $event) {
    $request_params = $event->getRequestParameters();

    return isset($request_params['id']);
  }

  /**
   * Returns TRUE if exception is related to an identifiable index.
   *
   * @param \Drupal\elasticsearch_helper\Event\ElasticsearchOperationExceptionEvent $event
   *
   * @return bool
   */
  protected function isIdentifiableIndex(ElasticsearchOperationExceptionEvent $event) {
    $request_params = $event->getRequestParameters();

    return isset($request_params['index']);
  }

  /**
   * Returns message context for identified documents.
   *
   * @param \Drupal\elasticsearch_helper\Event\ElasticsearchOperationExceptionEvent $event
   *
   * @return array
   */
  protected function getIdentifiedDocumentContext(ElasticsearchOperationExceptionEvent $event) {
    $request_params = $event->getRequestParameters() + [
      'index' => NULL,
      'id' => NULL,
    ];

    return [
      '@exception_message' => $event->getException()->getMessage(),
      '@index' => $request_params['index'],
      '@id' => $request_params['id'],
    ];
  }

  /**
   * Returns message context for identified index.
   *
   * @param \Drupal\elasticsearch_helper\Event\ElasticsearchOperationExceptionEvent $event
   *
   * @return array
   */
  protected function getIdentifiedIndexContext(ElasticsearchOperationExceptionEvent $event) {
    $request_params = $event->getRequestParameters() + [
      'index' => NULL,
    ];

    return [
      '@exception_message' => $event->getException()->getMessage(),
      '@index' => $request_params['index'],
    ];
  }

}
