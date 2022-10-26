<?php

namespace Drupal\elasticsearch_helper\Plugin;

use Drupal\Component\Plugin\PluginInspectionInterface;

/**
 * Defines an interface for Elasticsearch index plugins.
 */
interface ElasticsearchIndexInterface extends PluginInspectionInterface {

  /**
   * Defines default document type.
   *
   * @deprecated Will be removed from the codebase when support for
   *   Elasticsearch 7 is removed.
   */
  const TYPE_DEFAULT = '_doc';

  /**
   * Returns the instance of Elasticsearch client.
   *
   * @return \Elastic\Elasticsearch\Client
   */
  public function getClient();

  /**
   * Put data into the Elasticsearch index.
   *
   * @param mixed $source
   *   The data to be indexed.
   */
  public function index($source);

  /**
   * Get record from Elasticsearch index.
   *
   * @param mixed $source
   *   The data to get.
   *
   * @return array
   *
   * @throws \Throwable
   */
  public function get($source);

  /**
   * Delete an entry from the Elasticsearch index.
   *
   * @param mixed $source
   *   The data to be used to determine which entry should be deleted.
   */
  public function delete($source);

  /**
   * Setup Elasticsearch indices, analyzers, templates, mappings, etc.
   */
  public function setup();

  /**
   * Returns index definition.
   *
   * @param array $context
   *   Additional context parameters.
   *
   * @return \Drupal\elasticsearch_helper\Elasticsearch\Index\IndexDefinition|null
   */
  public function getIndexDefinition(array $context = []);

  /**
   * Returns index mapping definition.
   *
   * @param array $context
   *   Additional context parameters.
   *
   * @return \Drupal\elasticsearch_helper\Elasticsearch\Index\MappingDefinition|null
   */
  public function getMappingDefinition(array $context = []);

  /**
   * Get an array of index names for this plugin.
   *
   * @return string[]
   *
   * @throws \Throwable
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
   *
   * @return array
   *
   * @throws \Throwable
   */
  public function search($params);

  /**
   * Wrapper around the Elasticsearch msearch() method.
   *
   * @param array $params
   *
   * @return array
   *
   * @throws \Throwable
   */
  public function msearch($params);

  /**
   * Wrapper around the Elasticsearch bulk() method.
   *
   * @param array $body
   *   The body of the bulk operation.
   */
  public function bulk($body);

  /**
   * Re-indexes all the content that the plugin manages.
   *
   * It is recommended to use the queue to reindex content.
   *
   * @param array $context
   *   Additional context parameters.
   */
  public function reindex(array $context = []);

}
