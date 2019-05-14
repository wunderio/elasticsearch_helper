<?php

namespace Drupal\elasticsearch_helper_content\Plugin\views\filter;

use Drupal\Core\Form\FormStateInterface;
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
        $this->valueOptions = array_map(function ($index) {
          return $index['label'];
        }, $this->definition['indices']);
      }
      else {
        $this->valueOptions = [];
      }
    }

    return $this->valueOptions;
  }

  /**
   * {@inheritdoc}
   *
   * Return only "in" operator.
   */
  public function operators() {
    $operators = parent::operators();

    return isset($operators['in']) ? ['in' => $operators['in']] : [];
  }

  /**
   * {@inheritdoc}
   */
  protected function valueSubmit($form, FormStateInterface $form_state) {
    // Unset "all" value.
    unset($form['value']['#value']['all']);

    parent::valueSubmit($form, $form_state);
  }

}
