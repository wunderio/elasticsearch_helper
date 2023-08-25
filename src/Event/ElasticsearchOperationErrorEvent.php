<?php

namespace Drupal\elasticsearch_helper\Event;

use Drupal\elasticsearch_helper\ElasticsearchRequestWrapperInterface;
use Drupal\elasticsearch_helper\Plugin\ElasticsearchIndexInterface;
use Drupal\Component\EventDispatcher\Event;

/**
 * Elasticsearch operation error event.
 *
 * This event should be used when a throwable object is caught in methods
 * defined in ElasticsearchIndexInterface.
 *
 * If error is caught during request to Elasticsearch, an instance of
 * ElasticsearchRequestWrapperInterface should be present in the event. If
 * error occurred before request wrapper object has been made (e.g., during
 * content serialization), request wrapper object will not be available.
 *
 * @see \Drupal\elasticsearch_helper\Plugin\ElasticsearchIndexInterface
 */
class ElasticsearchOperationErrorEvent extends Event {

  /**
   * Error that was caught.
   *
   * @var \Throwable
   */
  protected $error;

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
   * Elasticsearch request wrapper instance.
   *
   * Request wrapper instance will only be available for errors that were
   * thrown after request wrapper object has been created.
   *
   * @var \Drupal\elasticsearch_helper\ElasticsearchRequestWrapperInterface|null
   */
  protected $requestWrapper;

  /**
   * An object on which the operation is performed.
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
   * ElasticsearchOperationErrorEvent constructor.
   *
   * @param \Throwable $error
   * @param $operation
   *   The operation being performed.
   * @param \Drupal\elasticsearch_helper\Plugin\ElasticsearchIndexInterface $plugin_instance
   *   The Elasticsearch index plugin instance.
   * @param \Drupal\elasticsearch_helper\ElasticsearchRequestWrapperInterface|null $request_wrapper
   *   The request wrapper instance.
   * @param mixed|null $object
   *   The index-able object or an index name.
   * @param array $metadata
   *   The metadata related to the object.
   */
  public function __construct(\Throwable $error, $operation, ElasticsearchIndexInterface $plugin_instance, ElasticsearchRequestWrapperInterface $request_wrapper = NULL, $object = NULL, $metadata = []) {
    $this->error = $error;
    $this->operation = $operation;
    $this->pluginInstance = $plugin_instance;
    $this->requestWrapper = $request_wrapper;
    $this->object = $object;
    $this->metadata = $metadata;
  }

  /**
   * Returns the error.
   *
   * @return \Throwable
   */
  public function getError() {
    return $this->error;
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
   * Returns the Elasticsearch index plugin instance.
   *
   * @return \Drupal\elasticsearch_helper\Plugin\ElasticsearchIndexInterface
   */
  public function getPluginInstance() {
    return $this->pluginInstance;
  }

  /**
   * Returns the Elasticsearch request wrapper instance.
   *
   * @return \Drupal\elasticsearch_helper\ElasticsearchRequestWrapperInterface|null
   */
  public function getRequestWrapper() {
    return $this->requestWrapper;
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
   * Returns callback parameters from Elasticsearch request wrapper.
   *
   * @return array
   */
  public function getCallbackParameters() {
    if ($request_wrapper = $this->getRequestWrapper()) {
      return $request_wrapper->getCallbackParameters() ?: [];
    }

    return [];
  }

  /**
   * Returns message context for identified documents.
   *
   * @return array
   */
  public function getMessageContextArguments() {
    $result = [
      '@error' => $this->getError()->getMessage(),
    ];

    if ($request_wrapper = $this->getRequestWrapper()) {
      $result['@index'] = $request_wrapper->getDocumentIndex();

      $document_id = $request_wrapper->getDocumentId();

      if (!is_null($document_id)) {
        $result['@id'] = $document_id;
      }
    }

    return $result;
  }

}
