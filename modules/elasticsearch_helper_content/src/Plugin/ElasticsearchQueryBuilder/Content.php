<?php

namespace Drupal\elasticsearch_helper_content\Plugin\ElasticsearchQueryBuilder;

use Drupal\elasticsearch_helper_views\Plugin\ElasticsearchQueryBuilder\ElasticsearchQueryBuilderPluginBase;

/**
 * @ElasticsearchQueryBuilder(
 *   id = "elasticsearch_content",
 *   label = @Translation("Content"),
 *   description = @Translation("Query builder for indices built by Elasticsearch Helper Content module")
 * )
 */
class Content extends ElasticsearchQueryBuilderPluginBase {

  /**
   * {@inheritdoc}
   */
  public function buildQuery() {
    $query = [
      'index' => $this->getIndices(),
      'body' => [],
    ];

    return $query;
  }

  /**
   * Returns a list of indices ("elasticsearch_index" filter value).
   *
   * @return array
   */
  protected function getIndices() {
    // Get filter values.
    $values = $this->getFilterValues();

    if (isset($values['elasticsearch_index'])) {
      return $values['elasticsearch_index'];
    }

    return [];
  }

}
