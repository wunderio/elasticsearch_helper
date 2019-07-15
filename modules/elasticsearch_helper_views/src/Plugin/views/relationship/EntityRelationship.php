<?php

namespace Drupal\elasticsearch_helper_views\Plugin\views\relationship;

use Drupal\Core\Form\FormStateInterface;
use Drupal\views\Plugin\views\relationship\Standard;

/**
 * Implementation of the "entity_relationship" relationship plugin.
 *
 * @ingroup views_relationship_handlers
 *
 * @ViewsRelationship("entity_relationship")
 */
class EntityRelationship extends Standard {

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition) {
    // Set default base field value.
    $plugin_definition['base field'] = NULL;
    parent::__construct($configuration, $plugin_id, $plugin_definition);
  }

  /**
   * {@inheritdoc}
   */
  protected function defineOptions() {
    $options = parent::defineOptions();

    $options['entity_type_key'] = ['default' => ''];
    $options['entity_id_key'] = ['default' => ''];

    return $options;
  }

  /**
   * {@inheritdoc}
   */
  public function buildOptionsForm(&$form, FormStateInterface $form_state) {
    parent::buildOptionsForm($form, $form_state);

    // Disable required relationships.
    $form['required']['#access'] = FALSE;

    $form['entity_type_key'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Entity type field'),
      '#description' => $this->t('A field in Elasticsearch results which contains entity type name. To set a fixed value, prefix the string with @ (e.g., @node).'),
      '#default_value' => $this->options['entity_type_key'],
    ];

    $form['entity_id_key'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Entity ID field'),
      '#description' => $this->t('A field in Elasticsearch results which contains entity ID value.'),
      '#default_value' => $this->options['entity_id_key'],
      '#group' => 'entity_type_key',
    ];
  }

}
