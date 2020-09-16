<?php

namespace Drupal\elasticsearch_helper\Event;

use Drupal\elasticsearch_helper\Plugin\ElasticsearchIndexInterface;
use Symfony\Component\EventDispatcher\Event;

/**
 * Class ElasticsearchOperationRequestEvent
 */
class ElasticsearchOperationRequestEvent extends Event {

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
   * ElasticsearchOperationRequestEvent constructor.
   *
   * @param $operation
   * @param $callback
   * @param array $callback_parameters
   * @param \Drupal\elasticsearch_helper\Plugin\ElasticsearchIndexInterface $plugin_instance
   * @param mixed $object
   */
  public function __construct($operation, $callback, array $callback_parameters, ElasticsearchIndexInterface $plugin_instance, $object = NULL) {
    $this->operation = $operation;
    $this->callback = $callback;
    $this->callbackParameters = $callback_parameters;
    $this->pluginInstance = $plugin_instance;
    $this->object = $object;
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
   * Returns Elasticsearch index plugin instance.
   *
   * @return \Drupal\elasticsearch_helper\Plugin\ElasticsearchIndexInterface
   */
  public function getPluginInstance() {
    return $this->pluginInstance;
  }

  /**
   * Returns index-able object.
   *
   * @return mixed|null
   */
  public function &getObject() {
    return $this->object;
  }

}
