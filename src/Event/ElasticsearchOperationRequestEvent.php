<?php

namespace Drupal\elasticsearch_helper\Event;

use Symfony\Component\EventDispatcher\Event;

/**
 * Class ElasticsearchOperationRequestEvent
 */
class ElasticsearchOperationRequestEvent extends Event {

  /**
   * Elasticsearch operation request callable.
   *
   * @var callable
   */
  protected $callback;

  /**
   * Elasticsearch operation request callable parameters.
   *
   * @var array
   */
  protected $callbackParameters = [];

  /**
   * Elasticsearch operation event.
   *
   * @var \Drupal\elasticsearch_helper\Event\ElasticsearchOperationEvent|null
   */
  protected $operationEvent;

  /**
   * ElasticsearchOperationRequestEvent constructor.
   *
   * @param callable $callback
   * @param array $callback_parameters
   * @param \Drupal\elasticsearch_helper\Event\ElasticsearchOperationEvent|null $operation_event
   */
  public function __construct($callback, array $callback_parameters, ElasticsearchOperationEvent $operation_event = NULL) {
    $this->callback = $callback;
    $this->callbackParameters = $callback_parameters;
    $this->operationEvent = $operation_event;
  }

  /**
   * Returns request callback.
   *
   * @return callable
   */
  public function &getCallback() {
    return $this->callback;
  }

  /**
   * Returns request callback parameters.
   *
   * @return array
   */
  public function &getCallbackParameters() {
    return $this->callbackParameters;
  }

  /**
   * Returns Elasticsearch operation event.
   *
   * @return \Drupal\elasticsearch_helper\Event\ElasticsearchOperationEvent|null
   */
  public function &getOperationEvent() {
    return $this->operationEvent;
  }

}
