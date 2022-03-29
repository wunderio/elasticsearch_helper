<?php

namespace Drupal\elasticsearch_helper\Event;

use Drupal\elasticsearch_helper\ElasticsearchRequestWrapperInterface;
use Drupal\elasticsearch_helper\Plugin\ElasticsearchIndexInterface;
use Symfony\Component\EventDispatcher\Event;

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
   * Elasticsearch operation.
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
   * Index-able object.
   *
   * @var mixed|null
   */
  protected $object;

  /**
   * ElasticsearchOperationErrorEvent constructor.
   *
   * @param \Throwable $error
   * @param $operation
   * @param \Drupal\elasticsearch_helper\Plugin\ElasticsearchIndexInterface $plugin_instance
   * @param \Drupal\elasticsearch_helper\ElasticsearchRequestWrapperInterface $request_wrapper
   * @param mixed|null $object
   */
  public function __construct(\Throwable $error, $operation, ElasticsearchIndexInterface $plugin_instance, ElasticsearchRequestWrapperInterface $request_wrapper = NULL, $object = NULL) {
    $this->error = $error;
    $this->operation = $operation;
    $this->pluginInstance = $plugin_instance;
    $this->requestWrapper = $request_wrapper;
    $this->object = $object;
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
   * {@inheritdoc}
   */
  public function getPluginInstance() {
    return $this->pluginInstance;
  }

  /**
   * Returns Elasticsearch request wrapper instance.
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
      $result['@id'] = $request_wrapper->getDocumentId();
    }

    return $result;
  }

}
