<?php

namespace Drupal\elasticsearch_helper;

use Drupal\elasticsearch_helper\Event\ElasticsearchEvents;
use Drupal\elasticsearch_helper\Event\ElasticsearchOperationRequestEvent;
use Drupal\elasticsearch_helper\Event\ElasticsearchOperationRequestResultEvent;
use Drupal\elasticsearch_helper\Plugin\ElasticsearchIndexInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * Elasticsearch request wrapper class.
 */
class ElasticsearchRequestWrapper implements ElasticsearchRequestWrapperInterface {

  /**
   * @var \Symfony\Component\EventDispatcher\EventDispatcherInterface
   */
  protected $eventDispatcher;

  /**
   * Elasticsearch operation.
   *
   * @var string
   */
  protected $operation;

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
   * Elasticsearch index plugin instance.
   *
   * @var \Drupal\elasticsearch_helper\Plugin\ElasticsearchIndexInterface
   */
  protected $pluginInstance;

  /**
   * Index-able object.
   *
   * @var mixed|null
   */
  protected $object;

  /**
   * ElasticsearchRequestWrapper constructor.
   *
   * @param \Symfony\Component\EventDispatcher\EventDispatcherInterface $event_dispatcher
   * @param $operation
   * @param $callback
   * @param array $callback_parameters
   * @param \Drupal\elasticsearch_helper\Plugin\ElasticsearchIndexInterface $plugin_instance
   * @param null $object
   */
  public function __construct(EventDispatcherInterface $event_dispatcher, $operation, $callback, array $callback_parameters, ElasticsearchIndexInterface $plugin_instance, $object = NULL) {
    $this->eventDispatcher = $event_dispatcher;
    $this->operation = $operation;
    $this->callback = $callback;
    $this->callbackParameters = $callback_parameters;
    $this->pluginInstance = $plugin_instance;
    $this->object = $object;
  }

  /**
   * Returns the event dispatcher.
   *
   * @return \Symfony\Component\EventDispatcher\EventDispatcherInterface
   */
  protected function getEventDispatcher() {
    return $this->eventDispatcher;
  }

  /**
   * {@inheritdoc}
   */
  public function getOperation() {
    return $this->operation;
  }

  /**
   * {@inheritdoc}
   */
  public function &getCallback() {
    return $this->callback;
  }

  /**
   * {@inheritdoc}
   */
  public function &getCallbackParameters() {
    return $this->callbackParameters;
  }

  /**
   * {@inheritdoc}
   */
  public function getPluginInstance() {
    return $this->pluginInstance;
  }

  /**
   * {@inheritdoc}
   */
  public function &getObject() {
    return $this->object;
  }

  /**
   * {@inheritdoc}
   */
  public function execute() {
    // Create request event.
    $request_event = new ElasticsearchOperationRequestEvent($this);
    // Dispatch the request event.
    $this->getEventDispatcher()->dispatch(ElasticsearchEvents::OPERATION_REQUEST, $request_event);

    // Execute the request.
    $result = $this->executeCallback();

    // Dispatch the result event.
    $this->dispatchRequestResultEvent($result);

    return $result;
  }

  /**
   * Executes the callback and returns an instance of request result.
   *
   * @return \Drupal\elasticsearch_helper\ElasticsearchRequestResultInterface
   */
  protected function executeCallback() {
    $result = call_user_func_array($this->getCallback(), $this->getCallbackParameters());

    return new ElasticsearchRequestResult($this, $result);
  }

  /**
   * Dispatches request result event.
   *
   * @param \Drupal\elasticsearch_helper\ElasticsearchRequestResultInterface $result
   */
  protected function dispatchRequestResultEvent(ElasticsearchRequestResultInterface $result) {
    $result_event = new ElasticsearchOperationRequestResultEvent($result);
    $this->getEventDispatcher()->dispatch(ElasticsearchEvents::OPERATION_REQUEST_RESULT, $result_event);
  }

  /**
   * {@inheritdoc}
   */
  public function getDocumentId() {
    $callback_params = $this->getCallbackParameters();

    return isset($callback_params[0]['id']) ? $callback_params[0]['id'] : NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function getDocumentIndex() {
    $callback_params = $this->getCallbackParameters();

    return isset($callback_params[0]['index']) ? $callback_params[0]['index'] : NULL;
  }

}
