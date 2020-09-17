<?php

namespace Drupal\elasticsearch_helper\Event;

use Drupal\elasticsearch_helper\ElasticsearchRequestWrapper;
use Symfony\Component\EventDispatcher\Event;

/**
 * Class ElasticsearchOperationErrorEvent
 */
class ElasticsearchOperationErrorEvent extends Event {

  /**
   * Error that was caught.
   *
   * @var \Throwable
   */
  protected $error;

  /**
   * Elasticsearch operation.
   *
   * @var string
   */
  protected $operation;

  /**
   * Elasticsearch request wrapper instance.
   *
   * @var \Drupal\elasticsearch_helper\ElasticsearchRequestWrapper
   */
  protected $requestWrapper;

  /**
   * ElasticsearchOperationErrorEvent constructor.
   *
   * @param \Throwable $error
   * @param $operation
   * @param \Drupal\elasticsearch_helper\ElasticsearchRequestWrapper $request_wrapper
   */
  public function __construct(\Throwable $error, $operation, ElasticsearchRequestWrapper $request_wrapper = NULL) {
    $this->error = $error;
    $this->operation = $operation;
    $this->requestWrapper = $request_wrapper;
  }

  /**
   * Returns the error.
   *
   * @return \Throwable
   */
  public function getError() {
    return $this->error;
  }

  /**
   * Returns Elasticsearch operation.
   *
   * @return string
   */
  public function getOperation() {
    return $this->operation;
  }

  /**
   * Returns Elasticsearch request wrapper instance.
   *
   * @return \Drupal\elasticsearch_helper\ElasticsearchRequestWrapper
   */
  public function getRequestWrapper() {
    return $this->requestWrapper;
  }

  /**
   * Returns callback parameters from Elasticsearch request wrapper.
   *
   * @return array
   */
  public function getCallbackParameters() {
    if ($request_wrapper = $this->getRequestWrapper()) {
      return $request_wrapper->getCallbackParameters() ?: [];
    }

    return [];
  }

  /**
   * Returns message context for identified documents.
   *
   * @return array
   */
  public function getMessageContextArguments() {
    $request_wrapper = $this->getRequestWrapper();

    $result = [
      '@index' => $request_wrapper->getDocumentIndex(),
      '@id' => $request_wrapper->getDocumentId(),
      '@error' => $this->getError()->getMessage(),
    ];

    return $result;
  }

}
