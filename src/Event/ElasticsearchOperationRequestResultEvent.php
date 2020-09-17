<?php

namespace Drupal\elasticsearch_helper\Event;

use Drupal\elasticsearch_helper\ElasticsearchRequestResult;
use Symfony\Component\EventDispatcher\Event;

/**
 * Class ElasticsearchOperationRequestResultEvent
 */
class ElasticsearchOperationRequestResultEvent extends Event {

  /**
   * Elasticsearch request result.
   *
   * @var \Drupal\elasticsearch_helper\ElasticsearchRequestResult
   */
  protected $requestResult;

  /**
   * ElasticsearchOperationRequestResultEvent constructor.
   *
   * @param \Drupal\elasticsearch_helper\ElasticsearchRequestResult $result
   */
  public function __construct(ElasticsearchRequestResult $result) {
    $this->requestResult = $result;
  }

  /**
   * Returns Elasticsearch request result body.
   *
   * @return \Drupal\elasticsearch_helper\ElasticsearchRequestResult
   */
  public function getResult() {
    return $this->requestResult;
  }

  /**
   * Returns Elasticsearch request wrapper instance.
   *
   * @return \Drupal\elasticsearch_helper\ElasticsearchRequestWrapper
   */
  public function getRequestWrapper() {
    return $this->getResult()->getRequestWrapper();
  }

  /**
   * Returns message context for identified documents.
   *
   * @return array
   */
  public function getMessageContextArguments() {
    $request_wrapper = $this->getResult()->getRequestWrapper();

    $result = [
      '@index' => $request_wrapper->getDocumentIndex(),
      '@id' => $request_wrapper->getDocumentId(),
    ];

    return $result;
  }

}
