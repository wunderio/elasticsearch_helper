<?php

namespace Drupal\elasticsearch_helper\Plugin;

use Drupal\Component\Plugin\PluginInspectionInterface;
use Drupal\elasticsearch_helper\ElasticsearchClientBuilder;

/**
 * Defines an interface for Elasticsearch index plugins.
 */
interface ElasticsearchIndexInterface extends PluginInspectionInterface {

  /**
   * Get the Elasticsearch client.
   *
   * @return ElasticsearchClientBuilder
   */
  public function getClient();

  /**
   * Put data into the Elasticsearch index.
   *
   * @param array $source
   *   The data to be indexed.
   */
  public function index($source);

  /**
   * Get record from Elasticsearch index.
   *
   * @param array $source
   *   The data to get.
   */
  public function get($source);

  /**
   * Delete an entry from the Elasticsearch index.
   *
   * @param array $source
   *   The data to be used to determine which entry should be deleted.
   */
  public function delete($source);

  /**
   * Setup Elasticsearch indices, analyzers, templates, mappings, etc.
   */
  public function setup();

  /**
   * Get an array of index names for this plugin.
   *
   * @return array
   */
  public function getExistingIndices();

  /**
   * Delete all related Elasticsearch indices.
   */
  public function drop();

  /**
   * Wrapper around the Elasticsearch search() method.
   *
   * @param array $params
   */
  public function search($params);

  /**
   * Wrapper around the Elasticsearch msearch() method.
   *
   * @param array $params
   */
  public function msearch($params);

  /**
   * Wrapper around the Elasticsearch bulk() method.
   *
   * @param array $body
   *   The body of the bulk operation.
   */
  public function bulk($body);
}
