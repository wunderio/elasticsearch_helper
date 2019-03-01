<?php

/**
 * @file
 * Provide views data for Elasticsearch indices.
 */

use Drupal\elasticsearch_helper_content\ElasticsearchDataTypeDefinition;
use Drupal\Core\Render\Markup;
use Drupal\Component\Render\MarkupInterface;

/**
 * Implements hook_views_data().
 */
function elasticsearch_helper_content_views_data() {
  $data = [];

  /** @var \Drupal\elasticsearch_helper\Plugin\ElasticsearchIndexManager $elasticsearch_index_manager */
  $elasticsearch_index_manager = \Drupal::service('plugin.manager.elasticsearch_index.processor');
  /** @var \Drupal\elasticsearch_helper_content\ElasticsearchEntityNormalizerManagerInterface $elasticsearch_entity_normalizer_manager */
  $elasticsearch_entity_normalizer_manager = \Drupal::service('plugin.manager.elasticsearch_entity_normalizer');
  /** @var \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager */
  $entity_type_manager = \Drupal::service('entity_type.manager');
  /** @var \Drupal\Core\Entity\EntityFieldManagerInterface $entity_field_manager */
  $entity_field_manager = \Drupal::service('entity_field.manager');

  // Filter out content indices.
  $elasticsearch_content_indices = array_filter($elasticsearch_index_manager->getDefinitions(), function ($definition) {
    return strpos($definition['id'], 'content_index:') === 0;
  });

  // Track field appearance in various indices.
  $field_instances = [];
  // Track index names.
  $index_names = [];

  foreach ($elasticsearch_content_indices as $index_plugin_id => $content_index_definition) {
    try {
      $entity_type_id = $content_index_definition['entityType'];
      $bundle = $content_index_definition['bundle'];

      $index_name = $content_index_definition['indexName'];
      $index_names[$index_name] = $content_index_definition['label'];

      $bundle_configuration = $content_index_definition['configuration'];
      $normalizer = $bundle_configuration['normalizer'];

      // Get entity keys.
      $entity_keys = $entity_type_manager->getDefinition($entity_type_id)->getKeys();

      // Get field definitions.
      $field_definitions = $entity_field_manager->getFieldDefinitions($entity_type_id, $bundle);

      /** @var \Drupal\elasticsearch_helper_content\ElasticsearchNormalizerInterface $normalizer_instance */
      $normalizer_instance = $elasticsearch_entity_normalizer_manager->createInstance($normalizer, $bundle_configuration);

      foreach ($normalizer_instance->getPropertyDefinitions() as $field_name => $property) {
        // Translate field name into entity field name.
        $entity_field_name = isset($entity_keys[$field_name]) ? $entity_keys[$field_name] : $field_name;

        // Use label from field definition or convert field name
        // to Sentence case.
        if (isset($field_definitions[$entity_field_name])) {
          $field_label = $field_definitions[$entity_field_name]->getLabel();
        }
        else {
          $field_label = Markup::create(ucfirst(str_replace('_', ' ', $field_name)));
        }

        if ($property instanceof ElasticsearchDataTypeDefinition) {
          // Field instances are tracked by field name and Elasticsearch
          // data type.
          // There may be entity types with identical field names, but with
          // different field types (e.g., Comment as entity reference on
          // Node entity type and Comment as string on Taxonomy term entity
          // type. Search across multiple indices would not be possible if
          // different typed fields are combined into the same Views field
          // definition.
          $data_type = $property->getDataType();

          // Record field usage.
          $field_instances[$field_name][$data_type]['index_name'][] = $index_name;
          $field_instances[$field_name][$data_type]['label'][] = $field_label;
        }
        else {
          /** @var \Drupal\elasticsearch_helper_content\ElasticsearchDataTypeDefinition $property_item */
          foreach ($property as $property_name => $property_item) {
            $data_type = $property_item->getDataType();

            // Add sub-property to the label.
            if ($field_label instanceof MarkupInterface) {
              $property_label = t($field_label->getUntranslatedString() . ' (@property_name)', ['@property_name' => $property_name] + $field_label->getArguments());
            }
            else {
              $property_label = t('@label (@property_name)', ['@label' => $field_label, '@property_name' => $property_name]);
            }

            // Fields with multiple sub-properties are defined separately.
            $field_instances[$field_name . '.' . $property_name][$data_type]['index_name'][] = $index_name;
            $field_instances[$field_name . '.' . $property_name][$data_type]['label'][] = $property_label;
          }
        }
      }
    } catch (\Exception $e) {
      watchdog_exception('elasticsearch_helper_content', $e);
    }
  }

  foreach ($field_instances as $field_name => $field_name_instance) {
    foreach ($field_name_instance as $data_type => $field_instance) {
      $label = implode(', ', array_unique($field_instance['label']));

      $data['elasticsearch_result'][$field_name . '_' . $data_type] = [
        'title' => $label,
        'field' => [
          'title' => $label,
          'id' => 'elasticsearch_source',
          'source_field_override' => $field_name,
        ],
        'help' => t('Appears in: @indices with type <small><code>@data_type</code></small>.', ['@indices' => implode(', ', $field_instance['index_name']), '@data_type' => $data_type]),
      ];
    }
  }

  $data['elasticsearch_result']['elasticsearch_index'] = [
    'title' => t('Elasticsearch index'),
    'filter' => [
      'title' => t('Elasticsearch index'),
      'id' => 'elasticsearch_index',
      'indices' => $index_names,
    ],
    'help' => t('Elasticsearch content index filter.'),
  ];

  return $data;
}
