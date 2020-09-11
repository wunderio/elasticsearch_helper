<?php

namespace Drupal\elasticsearch_helper\Event;

use Drupal\elasticsearch_helper\Plugin\ElasticsearchIndexInterface;

/**
 * Class ElasticsearchOperationExceptionEvent
 */
class ElasticsearchOperationExceptionEvent extends ElasticsearchOperationStatusEventBase {

  /**
   * Exception that was caught.
   *
   * @var \Exception
   */
  protected $exception;

  /**
   * ElasticsearchExceptionEvent constructor.
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
   * Returns exception.
   *
   * @return \Exception
   */
  public function getException() {
    return $this->exception;
  }

}
