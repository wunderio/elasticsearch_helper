<?php

namespace Drupal\elasticsearch_helper\Event;

use Drupal\elasticsearch_helper\Plugin\ElasticsearchIndexInterface;
use Symfony\Component\EventDispatcher\Event;

/**
 * Elasticsearch serialization event.
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
class ElasticsearchSerializeEvent extends Event {

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
   * Context of the operation if available.
   *
   * @var array
   */
  protected $context = [];

  /**
   * Serialized data.
   *
   * Event listeners can populate this variable to inject the serialized data
   * before actual content serialization is run.
   *
   * @var mixed|null
   */
  protected $serializedData = NULL;

  /**
   * ElasticsearchSerializeEvent constructor.
   *
   * @param \Drupal\elasticsearch_helper\Plugin\ElasticsearchIndexInterface $plugin_instance
   * @param mixed|null $object
   * @param array $context
   * @param mixed|null $serialized_data
   */
  public function __construct(ElasticsearchIndexInterface $plugin_instance, $object = NULL, array $context = [], $serialized_data = NULL) {
    $this->pluginInstance = $plugin_instance;
    $this->object = $object;
    $this->context = $context;
    $this->serializedData = $serialized_data;
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
   * Returns the context of the operation if available.
   *
   * @return array
   */
  public function getContext() {
    return $this->context;
  }

  /**
   * Returns serialized data by reference.
   *
   * @return mixed
   */
  public function &serializedData() {
    return $this->serializedData;
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
