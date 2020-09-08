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
   *   Elasticsearch 6 is removed.
   */
  const TYPE_DEFAULT = '_doc';

  /**
   * Returns the instance of Elasticsearch client.
   *
   * @return \Elasticsearch\Client
   */
  public function getClient();

  /**
   * Put data into the Elasticsearch index.
   *
   * @param mixed $source
   *   The data to be indexed.
   *
   * @return array|null
   */
  public function index($source);

  /**
   * Get record from Elasticsearch index.
   *
   * @param mixed $source
   *   The data to get.
   *
   * @return array|null
   */
  public function get($source);

  /**
   * Delete an entry from the Elasticsearch index.
   *
   * @param mixed $source
   *   The data to be used to determine which entry should be deleted.
   *
   * @return array|null
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
   *
   * @return array|null
   */
  public function search($params);

  /**
   * Wrapper around the Elasticsearch msearch() method.
   *
   * @param array $params
   *
   * @return array|null
   */
  public function msearch($params);

  /**
   * Wrapper around the Elasticsearch bulk() method.
   *
   * @param array $body
   *   The body of the bulk operation.
   *
   * @return array|null
   */
  public function bulk($body);

  /**
   * Re-indexes all the content that the plugin manages.
   *
   * It is recommended to use the queue to reindex content.
   *
   * @param array $context
   *   Additional context parameters.
   *
   * @return mixed
   */
  public function reindex(array $context = []);

}
