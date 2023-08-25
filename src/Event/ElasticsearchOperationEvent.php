<?php

namespace Drupal\elasticsearch_helper\Event;

use Drupal\elasticsearch_helper\Plugin\ElasticsearchIndexInterface;
use Drupal\Component\EventDispatcher\Event;

/**
 * Elasticsearch operation event.
 *
 * This event should be used when Elasticsearch operation is about to be
 * performed. It can be a general high-level operation or index/document related
 * operation.
 *
 * Note: this event implements operation permission interface and event
 * listeners may prevent operation from being allowed. Always use
 * $event->isOperationAllowed() where event is being emitted to check if
 * operation is allowed to proceed.
 */
class ElasticsearchOperationEvent extends Event implements OperationPermissionInterface {

  use OperationPermissionTrait;

  /**
   * The Elasticsearch operation.
   *
   * @var string
   */
  protected $operation;

  /**
   * Elasticsearch index plugin instance.
   *
   * @var \Drupal\elasticsearch_helper\Plugin\ElasticsearchIndexInterface
   */
  protected $pluginInstance;

  /**
   * An object on which the operation is performed.
   *
   * For document index operation, the object is an array, an entity etc.
   * For index create operation, the object is an index name.
   *
   * For general high-level operations object can be NULL.
   *
   * @var mixed|null
   */
  protected $object;

  /**
   * Additional metadata related to the object.
   *
   * For index create operations, the metadata variable can be an array
   * containing the index settings object.
   *
   * @var array
   */
  protected $metadata;

  /**
   * ElasticsearchOperationEvent constructor.
   *
   * @param $operation
   *   The operation being performed.
   * @param \Drupal\elasticsearch_helper\Plugin\ElasticsearchIndexInterface $plugin_instance
   *   The Elasticsearch index plugin instance.
   * @param mixed|null $object
   *   The index-able object or an index name.
   * @param array $metadata
   *   The metadata related to the object.
   */
  public function __construct($operation, ElasticsearchIndexInterface $plugin_instance, $object = NULL, $metadata = []) {
    $this->operation = $operation;
    $this->pluginInstance = $plugin_instance;
    $this->object = $object;
    $this->metadata = $metadata;
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
   * Returns the actionable object.
   *
   * Value is returned by reference as actionable object can be of any type.
   *
   * @return mixed|null
   */
  public function &getObject() {
    return $this->object;
  }

  /**
   * Returns metadata related to the object.
   *
   * @return array
   */
  public function &getMetadata() {
    return $this->metadata;
  }

  /**
   * Returns the Elasticsearch index plugin instance.
   *
   * @return \Drupal\elasticsearch_helper\Plugin\ElasticsearchIndexInterface
   */
  public function getPluginInstance() {
    return $this->pluginInstance;
  }

}
