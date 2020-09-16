<?php

namespace Drupal\elasticsearch_helper\Event;

use Drupal\elasticsearch_helper\Plugin\ElasticsearchIndexInterface;

/**
 * Class ElasticsearchOperationResultEvent
 */
class ElasticsearchOperationResultEvent extends ElasticsearchOperationStatusEventBase {

  /**
   * Elasticsearch operation response array.
   *
   * @var array
   */
  protected $result;

  /**
   * ElasticsearchOperationResultEvent constructor.
   *
   * @param array $result
   * @param $operation
   * @param $object
   * @param array $request_parameters
   * @param \Drupal\elasticsearch_helper\Plugin\ElasticsearchIndexInterface $plugin_instance
   */
  public function __construct(array $result, $operation, $object, array $request_parameters, ElasticsearchIndexInterface $plugin_instance) {
    $this->result = $result;
    $this->operation = $operation;
    $this->object = $object;
    $this->requestParameters = $request_parameters;
    $this->pluginInstance = $plugin_instance;
  }

  /**
   * Returns Elasticsearch operation response result.
   *
   * @return array
   */
  public function getResult() {
    return $this->result;
  }

}
