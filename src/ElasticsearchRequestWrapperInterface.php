<?php

namespace Drupal\elasticsearch_helper;

/**
 * Defines Elasticsearch request wrapper interface.
 */
interface ElasticsearchRequestWrapperInterface {

  /**
   * Returns Elasticsearch operation.
   *
   * @return string
   */
  public function getOperation();

  /**
   * Returns request callback.
   *
   * @return callable
   */
  public function &getCallback();

  /**
   * Returns request callback parameters.
   *
   * @return array
   */
  public function &getCallbackParameters();

  /**
   * Returns Elasticsearch index plugin instance.
   *
   * @return \Drupal\elasticsearch_helper\Plugin\ElasticsearchIndexInterface
   */
  public function getPluginInstance();

  /**
   * Returns index-able object.
   *
   * Value is returned by reference as index-able object can be of any type.
   *
   * @return mixed|null
   */
  public function &getObject();

  /**
   * Executes the request and returns the request result instance.
   *
   * @return \Drupal\elasticsearch_helper\ElasticsearchRequestResult
   *
   * @throws \Throwable
   */
  public function execute();

  /**
   * Returns document ID from request callback parameters (if available).
   *
   * @return mixed|null
   */
  public function getDocumentId();

  /**
   * Returns document index from request callback parameters (if available).
   *
   * @return mixed|null
   */
  public function getDocumentIndex();

}
