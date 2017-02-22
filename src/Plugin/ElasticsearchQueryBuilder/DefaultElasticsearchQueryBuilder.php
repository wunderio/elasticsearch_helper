<?php

namespace Drupal\elasticsearch_helper_views\Plugin\ElasticsearchQueryBuilder;

use Drupal\elasticsearch_helper_views\ElasticsearchQueryBuilderInterface;
use Drupal\views\ViewExecutable;

/**
 * @ElasticsearchQueryBuilder(
 *   id = "default",
 *   label = @Translation("Default"),
 *   description = @Translation("Default Elasticsearch query builder; does nothing")
 * )
 */
class DefaultElasticsearchQueryBuilder extends ElasticsearchQueryBuilderPluginBase implements ElasticsearchQueryBuilderInterface {

  /**
   * {@inheritdoc}
   */
  public function buildQuery(ViewExecutable $view) {
    return [];
  }

}
