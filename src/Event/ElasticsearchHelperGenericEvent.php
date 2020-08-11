<?php

namespace Drupal\elasticsearch_helper\Event;

use Drupal\elasticsearch_helper\Plugin\ElasticsearchIndexInterface;
use Symfony\Component\EventDispatcher\Event;

/**
 * Class ElasticsearchHelperGenericEvent
 */
class ElasticsearchHelperGenericEvent extends Event {

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
   * ElasticsearchHelperGenericEvent constructor.
   *
   * @param $callback
   * @param array $callback_parameters
   * @param \Drupal\elasticsearch_helper\Plugin\ElasticsearchIndexInterface $plugin_instance
   */
  public function __construct($callback, array $callback_parameters, ElasticsearchIndexInterface $plugin_instance) {
    $this->callback = $callback;
    $this->callbackParameters = $callback_parameters;
    $this->pluginInstance = $plugin_instance;
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

}
