<?php

namespace Drupal\elasticsearch_helper\Event;

use Drupal\elasticsearch_helper\Plugin\ElasticsearchIndexInterface;
use Symfony\Component\EventDispatcher\Event;

/**
 * Class ElasticsearchExceptionEvent
 */
class ElasticsearchExceptionEvent extends Event {

  /**
   * Exception that was caught.
   *
   * @var \Exception
   */
  protected $exception;

  /**
   * Elasticsearch operation.
   *
   * @var string
   */
  protected $operation;

  /**
   * Index-able object.
   *
   * @var mixed|null
   */
  protected $object;

  /**
   * Contains request parameters, if exception was related to request.
   *
   * @var array|null
   */
  protected $requestParameters;

  /**
   * Elasticsearch index plugin instance.
   *
   * @var \Drupal\elasticsearch_helper\Plugin\ElasticsearchIndexInterface
   */
  protected $pluginInstance;

  /**
   * ElasticsearchOperationEvent constructor.
   *
   * @param \Exception $exception
   * @param $operation
   * @param $object
   * @param $request_parameters
   * @param \Drupal\elasticsearch_helper\Plugin\ElasticsearchIndexInterface $plugin_instance
   */
  public function __construct(\Exception $exception, $operation, $object, $request_parameters, ElasticsearchIndexInterface $plugin_instance) {
    $this->exception = $exception;
    $this->operation = $operation;
    $this->object = $object;
    $this->requestParameters = $request_parameters;
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
   * Returns exception.
   *
   * @return \Exception
   */
  public function getException() {
    return $this->exception;
  }

  /**
   * Returns index-able object.
   *
   * @return mixed|null
   */
  public function &getObject() {
    return $this->object;
  }

  /**
   * Returns request parameters (if available).
   *
   * @return array|null
   */
  public function getRequestParameters() {
    return $this->requestParameters;
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
