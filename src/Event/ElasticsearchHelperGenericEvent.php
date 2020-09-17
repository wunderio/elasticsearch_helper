<?php

namespace Drupal\elasticsearch_helper\Event;

use Drupal\elasticsearch_helper\Plugin\ElasticsearchIndexInterface;
use Symfony\Component\EventDispatcher\Event;

/**
 * Class ElasticsearchHelperGenericEvent
 */
class ElasticsearchHelperGenericEvent extends Event {

  /**
   * Elasticsearch operation.
   *
   * @var string
   */
  protected $operation;

  /**
   * Elasticsearch operation callable.
   *
   * @var callable
   */
  protected $callback;

  /**
   * Elasticsearch operation callable parameters.
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
   * ElasticsearchHelperGenericEvent constructor.
   *
   * @param $operation
   * @param $callback
   * @param array $callback_parameters
   * @param \Drupal\elasticsearch_helper\Plugin\ElasticsearchIndexInterface $plugin_instance
   */
  public function __construct($operation, $callback, array $callback_parameters, ElasticsearchIndexInterface $plugin_instance) {
    $this->operation = $operation;
    $this->callback = $callback;
    $this->callbackParameters = $callback_parameters;
    $this->pluginInstance = $plugin_instance;
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
   * Returns callback.
   *
   * @return callable
   */
  public function &getCallback() {
    return $this->callback;
  }

  /**
   * Returns callback parameters.
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

}
