<?php

namespace Drupal\elasticsearch_helper;

use Drupal\elasticsearch_helper\Event\ElasticsearchEvents;
use Drupal\elasticsearch_helper\Event\ElasticsearchOperationRequestEvent;
use Drupal\elasticsearch_helper\Event\ElasticsearchOperationResultEvent;

/**
 * Class ElasticsearchRequestWrapper
 */
class ElasticsearchRequestWrapper {

  /**
   * @var \Symfony\Component\EventDispatcher\EventDispatcherInterface
   */
  protected $eventDispatcher;

  /**
   * Request event
   *
   * @var \Symfony\Component\EventDispatcher\Event
   */
  protected $event;

  /**
   * Request result.
   *
   * @var array|mixed
   */
  protected $result;

  /**
   * ElasticsearchRequestWrapper constructor.
   *
   * @param $operation
   * @param $callback
   * @param $callback_parameters
   * @param $plugin_instance
   * @param null $object
   */
  public function __construct($operation, $callback, $callback_parameters, $plugin_instance, $object = NULL) {
    $this->event = new ElasticsearchOperationRequestEvent($operation, $callback, [$callback_parameters], $plugin_instance, $object);
  }

  /**
   * Returns the event dispatcher.
   *
   * @return \Symfony\Component\EventDispatcher\EventDispatcherInterface
   */
  protected function getEventDispatcher() {
    if (!$this->eventDispatcher) {
      $this->eventDispatcher = \Drupal::service('event_dispatcher');
    }

    return $this->eventDispatcher;
  }

  protected function dispatchOperationResultEvent(array $result, $operation, $source = NULL, array $request_params = []) {
    $event = new ElasticsearchOperationResultEvent($result, $operation, $source, $request_params, $this);
    $this->getEventDispatcher()->dispatch(ElasticsearchEvents::OPERATION_RESULT, $event);

    return $event;
  }

  /**
   * Executes the request.
   *
   * @return static
   *
   * @throws \Throwable
   */
  public function execute() {
    // Dispatch the event.
    $this->getEventDispatcher()->dispatch(ElasticsearchEvents::OPERATION_REQUEST, $this->event);
    // Execute the request.
    $this->result = call_user_func_array($this->event->getCallback(), $this->event->getCallbackParameters());

    // Dispatch the result event.
    $event = new ElasticsearchOperationResultEvent($result, $operation, $source, $request_params, $this);
    $this->getEventDispatcher()->dispatch(ElasticsearchEvents::OPERATION_RESULT, $event);

    return $this;
  }

  /**
   * Returns request event.
   *
   * @return \Drupal\elasticsearch_helper\Event\ElasticsearchOperationRequestEvent::__construct
   */
  public function getEvent() {
    return $this->event;
  }

  /**
   * Returns request response result.
   *
   * @return array|mixed
   */
  public function getResult() {
    return $this->result;
  }

}
