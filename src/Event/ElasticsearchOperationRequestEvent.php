<?php

namespace Drupal\elasticsearch_helper\Event;

use Drupal\elasticsearch_helper\ElasticsearchRequestWrapperInterface;
use Drupal\Component\EventDispatcher\Event;

/**
 * Elasticsearch operation request event.
 *
 * This event should be used for Elasticsearch operation where request to
 * Elasticsearch is about to be performed via callback.
 *
 * @see \Drupal\elasticsearch_helper\Event\ElasticsearchHelperCallbackEvent
 */
class ElasticsearchOperationRequestEvent extends Event {

  /**
   * Elasticsearch request wrapper instance.
   *
   * @var \Drupal\elasticsearch_helper\ElasticsearchRequestWrapperInterface
   */
  protected $requestWrapper;

  /**
   * ElasticsearchOperationRequestEvent constructor.
   *
   * @param \Drupal\elasticsearch_helper\ElasticsearchRequestWrapperInterface $request_wrapper
   */
  public function __construct(ElasticsearchRequestWrapperInterface $request_wrapper) {
    $this->requestWrapper = $request_wrapper;
  }

  /**
   * Returns Elasticsearch request wrapper instance.
   *
   * @return \Drupal\elasticsearch_helper\ElasticsearchRequestWrapperInterface
   */
  public function getRequestWrapper() {
    return $this->requestWrapper;
  }

  /**
   * Returns request callback.
   *
   * @return callable
   */
  public function &getCallback() {
    return $this->getRequestWrapper()->getCallback();
  }

  /**
   * Returns callback parameters from Elasticsearch request wrapper.
   *
   * @return array
   */
  public function &getCallbackParameters() {
    return $this->getRequestWrapper()->getCallbackParameters();
  }

}
