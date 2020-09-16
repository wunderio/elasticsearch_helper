<?php

namespace Drupal\elasticsearch_helper\Event;

use Drupal\elasticsearch_helper\Plugin\ElasticsearchIndexInterface;

/**
 * Class ElasticsearchOperationErrorEvent
 */
class ElasticsearchOperationErrorEvent extends ElasticsearchOperationStatusEventBase {

  /**
   * Error that was caught.
   *
   * @var \Throwable
   */
  protected $error;

  /**
   * ElasticsearchOperationErrorEvent constructor.
   *
   * @param \Throwable $error
   * @param $operation
   * @param $object
   * @param array $request_parameters
   * @param \Drupal\elasticsearch_helper\Plugin\ElasticsearchIndexInterface $plugin_instance
   */
  public function __construct(\Throwable $error, $operation, $object, array $request_parameters, ElasticsearchIndexInterface $plugin_instance) {
    $this->error = $error;
    $this->operation = $operation;
    $this->object = $object;
    $this->requestParameters = $request_parameters;
    $this->pluginInstance = $plugin_instance;
  }

  /**
   * Returns the error.
   *
   * @return \Throwable
   */
  public function getError() {
    return $this->error;
  }

}
