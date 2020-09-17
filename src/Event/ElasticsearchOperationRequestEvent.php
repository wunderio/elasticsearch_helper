<?php

namespace Drupal\elasticsearch_helper\Event;

use Drupal\elasticsearch_helper\ElasticsearchRequestWrapper;
use Symfony\Component\EventDispatcher\Event;

/**
 * Class ElasticsearchOperationRequestEvent
 */
class ElasticsearchOperationRequestEvent extends Event {

  /**
   * Elasticsearch request wrapper instance.
   *
   * @var \Drupal\elasticsearch_helper\ElasticsearchRequestWrapper
   */
  protected $requestWrapper;

  /**
   * ElasticsearchOperationRequestEvent constructor.
   *
   * @param \Drupal\elasticsearch_helper\ElasticsearchRequestWrapper $request_wrapper
   */
  public function __construct(ElasticsearchRequestWrapper $request_wrapper) {
    $this->requestWrapper = $request_wrapper;
  }

  /**
   * Returns Elasticsearch request wrapper instance.
   *
   * @return \Drupal\elasticsearch_helper\ElasticsearchRequestWrapper
   */
  public function getRequestWrapper() {
    return $this->requestWrapper;
  }

}
