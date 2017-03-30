<?php

namespace Drupal\elasticsearch_helper_views;

use Drupal\Component\Plugin\PluginInspectionInterface;
use Drupal\views\ViewExecutable;

/**
 * Defines an interface for Elasticsearch query builder plugins.
 */
interface ElasticsearchQueryBuilderInterface extends PluginInspectionInterface {

  /**
   * Builds Elasticsearch query based on given query/filter values.
   *
   * The query array needs to be compatible with
   * \Elasticsearch\Client::search().
   *
   * @param ViewExecutable $view
   *
   * @return array
   *
   * @see \Elasticsearch\Client::search()
   */
  public function buildQuery(ViewExecutable $view);

  /**
   * Returns filter values from a view.
   *
   * @param \Drupal\views\ViewExecutable $view
   *
   * @return array
   */
  public function getValuesFromView(ViewExecutable $view);

  /**
   * Returns argument values from a view.
   *
   * @param \Drupal\views\ViewExecutable $view
   *
   * @return array
   */
  public function getArgumentsFromView(ViewExecutable $view);

}
