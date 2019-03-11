<?php

namespace Drupal\elasticsearch_helper_views\Plugin\views\field;

use Drupal\Core\Form\FormStateInterface;
use Drupal\views\Plugin\views\field\FieldPluginBase;
use Drupal\views\ResultRow;

/**
 * Renders a plain value from the Elasticsearch result.
 *
 * @ingroup views_field_handlers
 *
 * @ViewsField("elasticsearch_source")
 */
class Source extends FieldPluginBase {

  use SourceValueTrait;

  /**
   * {@inheritdoc}
   */
  public function defineOptions() {
    $options = parent::defineOptions();

    $options['source_field'] = ['default' => ''];

    return $options;
  }

  /**
   * {@inheritdoc}
   */
  public function buildOptionsForm(&$form, FormStateInterface $form_state) {
    parent::buildOptionsForm($form, $form_state);

    $t_args_description = ['@separator' => $this->nestedValueSeparator, '@example' => implode($this->nestedValueSeparator, ['abc', 'xyz'])];
    $form['source_field'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Source field'),
      '#description' => $this->t('Enter the key in the "_source" field. For nested fields separate the fields with a separator ("@separator"). Example: @example', $t_args_description),
      '#required' => TRUE,
      '#default_value' => $this->options['source_field']
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function adminLabel($short = FALSE) {
    $label = parent::adminLabel();

    if ($this->options['source_field'] != '') {
      return $label . ' (' . $this->options['source_field'] . ')';
    }

    return $label;
  }

  /**
   * {@inheritdoc}
   */
  public function render(ResultRow $row) {
    $source_field = $this->options['source_field'];

    return $this->getValue($row, $source_field);
  }

}
