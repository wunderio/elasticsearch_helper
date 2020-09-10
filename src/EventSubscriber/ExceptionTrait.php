<?php

namespace Drupal\elasticsearch_helper\EventSubscriber;

use Drupal\elasticsearch_helper\Event\ElasticsearchExceptionEvent;

/**
 * Provides common methods for data retrieval from exception event.
 */
trait ExceptionTrait {

  /**
   * Returns TRUE id exception is related to an identifiable document.
   *
   * @param \Drupal\elasticsearch_helper\Event\ElasticsearchExceptionEvent $event
   *
   * @return bool
   */
  protected function isIdentifiableDocument(ElasticsearchExceptionEvent $event) {
    $request_params = $event->getRequestParameters();

    return isset($request_params['id']);
  }

  /**
   * Returns TRUE if exception is related to an identifiable index.
   *
   * @param \Drupal\elasticsearch_helper\Event\ElasticsearchExceptionEvent $event
   *
   * @return bool
   */
  protected function isIdentifiableIndex(ElasticsearchExceptionEvent $event) {
    $request_params = $event->getRequestParameters();

    return isset($request_params['index']);
  }

  /**
   * Returns message context for identified documents.
   *
   * @param \Drupal\elasticsearch_helper\Event\ElasticsearchExceptionEvent $event
   *
   * @return array
   */
  protected function getIdentifiedDocumentContext(ElasticsearchExceptionEvent $event) {
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
   * @param \Drupal\elasticsearch_helper\Event\ElasticsearchExceptionEvent $event
   *
   * @return array
   */
  protected function getIdentifiedIndexContext(ElasticsearchExceptionEvent $event) {
    $request_params = $event->getRequestParameters() + [
      'index' => NULL,
    ];

    return [
      '@index' => $request_params['index'],
    ];
  }

}
