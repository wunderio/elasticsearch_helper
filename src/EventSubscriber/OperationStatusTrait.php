<?php

namespace Drupal\elasticsearch_helper\EventSubscriber;

use Drupal\elasticsearch_helper\Event\ElasticsearchOperationStatusEventBase;

/**
 * Provides common methods for data retrieval from status request parameters.
 */
trait OperationStatusTrait {

  /**
   * Returns TRUE if status is related to an identifiable document.
   *
   * @param \Drupal\elasticsearch_helper\Event\ElasticsearchOperationStatusEventBase $event
   *
   * @return bool
   */
  protected function isIdentifiableDocument(ElasticsearchOperationStatusEventBase $event) {
    $request_params = $event->getRequestParameters();

    return isset($request_params['id']);
  }

  /**
   * Returns TRUE if status is related to an identifiable index.
   *
   * @param \Drupal\elasticsearch_helper\Event\ElasticsearchOperationStatusEventBase $event
   *
   * @return bool
   */
  protected function isIdentifiableIndex(ElasticsearchOperationStatusEventBase $event) {
    $request_params = $event->getRequestParameters();

    return isset($request_params['index']);
  }

  /**
   * Returns message context for identified documents.
   *
   * @param \Drupal\elasticsearch_helper\Event\ElasticsearchOperationStatusEventBase $event
   *
   * @return array
   */
  protected function getIdentifiedDocumentContext(ElasticsearchOperationStatusEventBase $event) {
    $request_params = $event->getRequestParameters() + [
      'index' => NULL,
      'id' => NULL,
    ];

    return [
      '@index' => $request_params['index'],
      '@id' => $request_params['id'],
    ];
  }

  /**
   * Returns message context for identified index.
   *
   * @param \Drupal\elasticsearch_helper\Event\ElasticsearchOperationStatusEventBase $event
   *
   * @return array
   */
  protected function getIdentifiedIndexContext(ElasticsearchOperationStatusEventBase $event) {
    $request_params = $event->getRequestParameters() + [
      'index' => NULL,
    ];

    return [
      '@index' => $request_params['index'],
    ];
  }

}
