<?php

namespace Drupal\elasticsearch_helper_views;

use Drupal\Component\Plugin\PluginInspectionInterface;
use Drupal\Core\Cache\CacheableDependencyInterface;

/**
 * Defines an interface for Elasticsearch query builder plugins.
 */
interface ElasticsearchQueryBuilderInterface extends PluginInspectionInterface, CacheableDependencyInterface {

  /**
   * Builds Elasticsearch query based on given query/filter values.
   *
   * The query array needs to be compatible with
   * \Elasticsearch\Client::search().
   *
   * @return array
   *
   * @see \Elasticsearch\Client::search()
   */
  public function buildQuery();

  /**
   * Returns filter values from a view.
   *
   * @return array
   */
  public function getFilterValues();

  /**
   * Returns argument values from a view.
   *
   * @return array
   */
  public function getArgumentValues();

  /**
   * Returns sort values from a view.
   *
   * @return array
   *
   */
  public function getSortValues();

}
