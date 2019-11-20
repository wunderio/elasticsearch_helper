<?php

namespace Drupal\elasticsearch_helper_content\Plugin\ElasticsearchNormalizer\Field;

use Drupal\Core\Field\FieldItemInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\elasticsearch_helper_content\ElasticsearchDataTypeDefinition;
use Drupal\elasticsearch_helper_content\ElasticsearchFieldNormalizerBase;

/**
 * @ElasticsearchFieldNormalizer(
 *   id = "text",
 *   label = @Translation("Text/Keyword"),
 *   field_types = {
 *     "string",
 *     "uuid",
 *     "language",
 *     "text",
 *     "text_long",
 *     "text_with_summary",
 *     "list_string"
 *   }
 * )
 */
class TextNormalizer extends ElasticsearchFieldNormalizerBase {

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
    $field_type = $this->configuration['storage_type'];
    $definition = ElasticsearchDataTypeDefinition::create($field_type);

    // Store raw value as keyword field.
    if ($this->configuration['store_raw']) {
      $definition->addField('raw', ElasticsearchDataTypeDefinition::create('keyword'));
    }

    return $definition;
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'storage_type' => 'text',
      'store_raw' => FALSE,
    ] + parent::defaultConfiguration();
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    return [
      'storage_type' => [
        '#type' => 'select',
        '#title' => t('Storage type'),
        '#options' => [
          'text' => t('Text'),
          'keyword' => t('Keyword'),
        ],
        '#default_value' => $this->configuration['storage_type'],
      ],
      'store_raw' => [
        '#type' => 'checkbox',
        '#title' => t('Store raw value as keyword'),
        '#weight' => 50,
        '#default_value' => $this->configuration['store_raw'],
        '#states' => [
          'invisible' => [
            ':input[name*="storage_type"]' => [
              'value' => 'keyword',
            ]
          ],
        ],

      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    $this->configuration['storage_type'] = $form_state->getValue('storage_type');
    $this->configuration['store_raw'] = $form_state->getValue('store_raw');

    // Do not store raw value if storage type is keyword.
    if ($this->configuration['storage_type'] == 'keyword') {
      $this->configuration['store_raw'] = FALSE;
    }
  }

}
