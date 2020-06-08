<?php

namespace Drupal\elasticsearch_helper\Event;

use Symfony\Component\EventDispatcher\Event;

/**
 * Class ElasticsearchOperationEvent
 */
class ElasticsearchOperationEvent extends Event {

  /**
   * Elasticsearch operation.
   *
   * @var string
   */
  protected $operation;

  /**
   * Index-able object.
   *
   * @var mixed
   */
  protected $object;

  /**
   * ElasticsearchOperationEvent constructor.
   *
   * @param $operation
   * @param $object
   */
  public function __construct($operation, $object) {
    $this->operation = $operation;
    $this->object = $object;
  }

  /**
   * Returns Elasticsearch operation.
   *
   * @return string
   */
  public function &getOperation() {
    return $this->operation;
  }

  /**
   * Returns index-able object.
   *
   * @return mixed
   */
  public function &getObject() {
    return $this->object;
  }

}
