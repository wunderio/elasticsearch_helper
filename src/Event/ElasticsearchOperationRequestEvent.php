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
  protected $callable;

  /**
   * Elasticsearch operation request callable parameters.
   *
   * @var array
   */
  protected $callableParameters = [];

  /**
   * Elasticsearch operation event.
   *
   * @var \Drupal\elasticsearch_helper\Event\ElasticsearchOperationEvent|null
   */
  protected $operationEvent;

  /**
   * ElasticsearchOperationRequestEvent constructor.
   *
   * @param callable $callable
   * @param array $callable_parameters
   * @param \Drupal\elasticsearch_helper\Event\ElasticsearchOperationEvent|null $operation_event
   */
  public function __construct($callable, array $callable_parameters, ElasticsearchOperationEvent $operation_event = NULL) {
    $this->callable = $callable;
    $this->callableParameters = $callable_parameters;
    $this->operationEvent = $operation_event;
  }

  /**
   * Returns request callable.
   *
   * @return callable
   */
  public function &getCallable() {
    return $this->callable;
  }

  /**
   * Returns request callable parameters.
   *
   * @return array
   */
  public function &getCallableParameters() {
    return $this->callableParameters;
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
