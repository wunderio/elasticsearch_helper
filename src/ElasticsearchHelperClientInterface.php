<?php

namespace Drupal\elasticsearch_helper;

/**
 * Interface for Elasticsearch Helper Client.
 */
interface ElasticsearchHelperClientInterface {

  /**
   * Index operation.
   *
   * @param array $parameters
   *   The array of request parameters.
   */
  public function index(array $parameters);

  /**
   * Update operation.
   *
   * @param array $parameters
   *   The array of request parameters.
   */
  public function update(array $parameters);

  /**
   * Delete operation.
   *
   * @param array $parameters
   *   The array of request parameters.
   */
  public function delete(array $parameters);

  /**
   * Search operation.
   *
   * @param array $parameters
   *   The array of request parameters.
   */
  public function search(array $parameters);

  /**
   * MSearch operation.
   *
   * @param array $parameters
   *   The array of request parameters.
   */
  public function msearch(array $parameters);

  /**
   * Bulk operation.
   *
   * @param array $parameters
   *   The array of request parameters.
   */
  public function bulk(array $parameters);

  /**
   * Get indices.
   */
  public function indices();

  /**
   * Create index operation.
   *
   * @param array $parameters
   *   The array of request parameters.
   */
  public function createIndex(array $parameters);

  /**
   * Delete index operation.
   *
   * @param array $parameters
   *   The array of request parameters.
   */
  public function deleteIndex(array $parameters);

  /**
   * Get cluster health.
   */
  public function health();

  /**
   * Check if index exists.
   *
   * @param string $name
   *   The name of the index.
   */
  public function indexExists($name);

  /**
   * Check if template exists.
   *
   * @param string $name
   *   The template name.
   */
  public function templateExists($name);

  /**
   * Put a template to index.
   *
   * @param array $parameters
   *   The array of request parameters.
   */
  public function putTemplate(array $parameters);

}
