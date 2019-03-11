<?php

namespace Drupal\elasticsearch_helper_content\Plugin\views\filter;

use Drupal\views\Plugin\views\filter\InOperator;

/**
 * Filter handler which allows searching in selected indices.
 *
 * @ingroup views_filter_handlers
 *
 * @ViewsFilter("elasticsearch_content_index")
 */
class ElasticsearchIndex extends InOperator {

  /**
   * {@inheritdoc}
   */
  public function getValueOptions() {
    if (!isset($this->valueOptions)) {
      if (isset($this->definition['indices'])) {
        $this->valueOptions = $this->definition['indices'];
      }
      else {
        $this->valueOptions = [];
      }
    }

    return $this->valueOptions;
  }

}
