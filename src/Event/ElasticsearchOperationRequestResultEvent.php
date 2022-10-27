<?php

namespace Drupal\elasticsearch_helper\Event;

use Drupal\elasticsearch_helper\ElasticsearchRequestResultInterface;
use Drupal\Component\EventDispatcher\Event;

/**
 * Elasticsearch operation request result event.
 *
 * This event should be used after resulting response is received from
 * Elasticsearch.
 */
class ElasticsearchOperationRequestResultEvent extends Event {

  /**
   * Elasticsearch request result.
   *
   * @var \Drupal\elasticsearch_helper\ElasticsearchRequestResultInterface
   */
  protected $requestResult;

  /**
   * ElasticsearchOperationRequestResultEvent constructor.
   *
   * @param \Drupal\elasticsearch_helper\ElasticsearchRequestResultInterface $result
   */
  public function __construct(ElasticsearchRequestResultInterface $result) {
    $this->requestResult = $result;
  }

  /**
   * Returns Elasticsearch request result body.
   *
   * @return \Drupal\elasticsearch_helper\ElasticsearchRequestResultInterface
   */
  public function getResult() {
    return $this->requestResult;
  }

  /**
   * Returns Elasticsearch request wrapper instance.
   *
   * @return \Drupal\elasticsearch_helper\ElasticsearchRequestWrapperInterface
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
    ];

    $document_id = $request_wrapper->getDocumentId();

    if (!is_null($document_id)) {
      $result['@id'] = $document_id;
    }

    return $result;
  }

}
