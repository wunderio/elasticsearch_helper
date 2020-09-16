<?php

namespace Drupal\elasticsearch_helper\Event;

use Symfony\Component\EventDispatcher\Event;

/**
 * Class ElasticsearchOperationStatusEventBase
 */
abstract class ElasticsearchOperationStatusEventBase extends Event {

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
   * Contains request parameters.
   *
   * @var array
   */
  protected $requestParameters;

  /**
   * Elasticsearch index plugin instance.
   *
   * @var \Drupal\elasticsearch_helper\Plugin\ElasticsearchIndexInterface
   */
  protected $pluginInstance;

  /**
   * Returns Elasticsearch operation.
   *
   * @return string
   */
  public function getOperation() {
    return $this->operation;
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
   * @return array
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
