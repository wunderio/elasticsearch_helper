<?php

namespace Drupal\elasticsearch_helper\Plugin;

use Drupal\Component\Plugin\PluginInspectionInterface;

/**
 * Defines an interface for Elasticsearch index plugins.
 */
interface ElasticsearchIndexInterface extends PluginInspectionInterface {

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
   * Delete all related Elasticsearch indices.
   */
  public function drop();

  /**
   * Wrapper around the Elasticsearch search() method.
   *
   * @param array $params
   */
  public function search($params);
}
