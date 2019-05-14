<?php

namespace Drupal\elasticsearch_helper_content\Plugin\ElasticsearchNormalizer\Field;

use Drupal\Core\Field\FieldItemInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\elasticsearch_helper_content\ElasticsearchDataTypeDefinition;
use Drupal\elasticsearch_helper_content\ElasticsearchFieldNormalizerBase;

/**
 * @ElasticsearchFieldNormalizer(
 *   id = "field_text",
 *   label = @Translation("Text/Keyword"),
 *   field_types = {
 *     "string",
 *     "uuid",
 *     "language",
 *     "text",
 *     "text_long",
 *     "text_with_summary",
 *     "list_string"
 *   },
 * )
 */
class ElasticsearchFieldTextNormalizer extends ElasticsearchFieldNormalizerBase {

  /**
   * {@inheritdoc}
   */
  public function getFieldItemValue(FieldItemInterface $item, array $context = []) {
    return $item->get('value')->getValue();
  }

  /**
   * {@inheritdoc}
   */
  public function getPropertyDefinitions() {
    $field_type = 'text';

    // Determine data type.
    switch ($this->configuration['storage_method']) {
      case 'keyword':
        $field_type = 'keyword';
        break;

      case 'text_keyword_field':
        $field_name = 'keyword';
        $field_definition = ElasticsearchDataTypeDefinition::create('keyword');
        break;

    }

    // Prepare definition.
    $definition = ElasticsearchDataTypeDefinition::create($field_type);

    // Add fields (if available).
    if (isset($field_name, $field_definition)) {
      $definition->addField($field_name, $field_definition);
    }

    return $definition;
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'storage_method' => 'text',
    ] + parent::defaultConfiguration();
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    return [
      'storage_method' => [
        '#type' => 'select',
        '#title' => t('Storage method'),
        '#options' => [
          'text' => t('Text'),
          'keyword' => t('Keyword'),
          'text_keyword_field' => t('Text with keyword field'),
        ],
        '#default_value' => $this->configuration['storage_method'],
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    $this->configuration['storage_method'] = $form_state->getValue('storage_method');
  }

}
