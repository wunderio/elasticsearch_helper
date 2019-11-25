<?php

namespace Drupal\elasticsearch_helper_content;

use Drupal\Component\Render\FormattableMarkup;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\elasticsearch_helper\Plugin\ElasticsearchIndexManager;
use Drupal\views\EntityViewsDataInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class ElasticsearchContentIndexViewsData
 */
class ElasticsearchContentIndexViewsData implements EntityViewsDataInterface, ContainerInjectionInterface {

  /**
   * @var \Drupal\elasticsearch_helper\Plugin\ElasticsearchIndexManager
   */
  protected $elasticsearchIndexManager;

  /**
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * @var \Drupal\Core\Entity\EntityFieldManagerInterface
   */
  protected $entityFieldManager;

  /**
   * @var \Drupal\Core\Language\LanguageManagerInterface
   */
  protected $languageManager;

  /**
   * @var \Drupal\elasticsearch_helper_content\ElasticsearchDataTypeRepository
   */
  protected $elasticsearchDataTypeRepository;

  /**
   * @var string
   */
  protected $fieldSeparator = '|';

  /**
   * ElasticsearchContentIndexViewsData constructor.
   *
   * @param \Drupal\elasticsearch_helper\Plugin\ElasticsearchIndexManager $elasticsearch_index_manager
   * @param \Drupal\elasticsearch_helper_content\ElasticsearchDataTypeRepository $data_type_repository
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   * @param \Drupal\Core\Entity\EntityFieldManagerInterface $entity_field_manager
   * @param \Drupal\Core\Language\LanguageManagerInterface $language_manager
   */
  public function __construct(ElasticsearchIndexManager $elasticsearch_index_manager, ElasticsearchDataTypeRepository $data_type_repository, EntityTypeManagerInterface $entity_type_manager, EntityFieldManagerInterface $entity_field_manager, LanguageManagerInterface $language_manager) {
    $this->elasticsearchIndexManager = $elasticsearch_index_manager;
    $this->elasticsearchDataTypeRepository = $data_type_repository;
    $this->entityTypeManager = $entity_type_manager;
    $this->entityFieldManager = $entity_field_manager;
    $this->languageManager = $language_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('plugin.manager.elasticsearch_index.processor'),
      $container->get('elasticsearch_helper_content.elasticsearch_data_type_repository'),
      $container->get('entity_type.manager'),
      $container->get('entity_field.manager'),
      $container->get('language_manager')
    );
  }

  /**
   * Returns content index instances.
   *
   * @return \Drupal\elasticsearch_helper_content\Plugin\ElasticsearchIndex\ContentIndex[]
   */
  protected function getContentIndexInstances() {
    // Filter out content indices.
    $content_index_definitions = array_filter($this->elasticsearchIndexManager->getDefinitions(), function ($definition) {
      return strpos($definition['id'], 'content_index:') === 0;
    });

    return array_map(function ($definition) {
      return $this->elasticsearchIndexManager->createInstance($definition['id']);
    }, $content_index_definitions);
  }

  /**
   * {@inheritdoc}
   */
  public function getViewsData() {
    $data = [];

    // Track field appearance in various indices.
    $field_instances = [];
    // Track index names.
    $index_names_all = [];

    foreach ($this->getContentIndexInstances() as $index_plugin_id => $content_index_instance) {
      try {
        $content_index_entity = $content_index_instance->getContentIndexEntity();
        $content_index_entity_label = $content_index_entity->label();

        $entity_type_id = $content_index_entity->getTargetEntityType();
        $bundle = $content_index_entity->getTargetBundle();

        // Get all index names content index plugin creates.
        $index_instance_index_names = $content_index_instance->getIndexNames();

        // Keep track of all index names.
        foreach ($index_instance_index_names as $langcode => $existing_name) {
          // Get index name label.
          $index_label = $content_index_entity_label;

          // Add language name if content index is multilingual.
          if ($langcode && $language = $this->languageManager->getLanguage($langcode)) {
            $index_label = new FormattableMarkup('@label (@language)', ['@label' => $index_label, '@language' => $language->getName()]);
          }

          $index_names_all[$existing_name] = [
            'label' => $index_label,
            'multilingual' => $content_index_entity->isMultilingual(),
          ];
        }

        /** @var \Drupal\elasticsearch_helper_content\ElasticsearchNormalizerInterface $normalizer_instance */
        $normalizer_instance = $content_index_entity->getNormalizerInstance();

        // Get entity keys.
        $entity_keys = $this->entityTypeManager->getDefinition($entity_type_id)->getKeys();

        // Get field definitions.
        $field_definitions = $this->entityFieldManager->getFieldDefinitions($entity_type_id, $bundle);

        foreach ($normalizer_instance->getPropertyDefinitions() as $field_name => $property) {
          // Translate field name into entity field name.
          $entity_field_name = isset($entity_keys[$field_name]) ? $entity_keys[$field_name] : $field_name;

          // Use label from field definition or convert field name
          // to Sentence case.
          if (isset($field_definitions[$entity_field_name])) {
            $field_label = $field_definitions[$entity_field_name]->getLabel();
          }
          else {
            $field_label = ucfirst(str_replace('_', ' ', $field_name));
          }
          $field_label = t('@label', ['@label' => $field_label]);

          /** @var \Drupal\elasticsearch_helper_content\ElasticsearchDataTypeDefinition[] $property_collection */
          // Some fields contain a single property while others might be
          // objects that contain multiple properties.
          // Do not add primary property if it has inner properties.
          $property_collection = $property->hasProperties() ? $property->getProperties() : [NULL => $property];

          foreach ($property_collection as $property_name => $property_item) {
            // Field names are dependent on their depth.
            $views_field_name_parts = [$field_name];
            $property_label_hint_parts = [];

            // Field instances are tracked by field name and Elasticsearch
            // data type.
            // There may be entity types with identical field names, but with
            // different field types (e.g., Comment as entity reference on
            // Node entity type and Comment as string on Taxonomy term entity
            // type. Search across multiple indices would not be possible if
            // different typed fields are combined into the same Views field
            // definition.
            $data_type = $property_item->getDataType();

            // Property names exist only for sub-properties of object type.
            if ($property_name) {
              $views_field_name_parts[] = $property_name;

              // Add sub-property to the label.
              $property_label_hint_parts[] = $property_name;
              $field_label = t('@label (@property_name)', ['@property_name' => sprintf('%s:%s', $field_name, $property_name)] + $field_label->getArguments());
            }

            // Prepare views field name.
            $views_field_name = implode($this->fieldSeparator, $views_field_name_parts);
            // Record field usage across index names.
            foreach ($index_instance_index_names as $index_name) {
              $field_instances[$views_field_name][$data_type]['index_name'][] = $index_name;
            }
            // There may be multiple labels for the same field name and type
            // across indices.
            $field_instances[$views_field_name][$data_type]['label'][] = $field_label;
            $field_instances[$views_field_name][$data_type]['views_options'] = $property_item->getViewsOptions();

            // Add property fields to views data (if available).
            /** @var \Drupal\elasticsearch_helper_content\ElasticsearchDataTypeDefinition $property_field_property */
            foreach ($property_item->getFields() as $property_field_name => $property_field_property) {
              $views_field_name_parts[] = $property_field_name;

              $property_field_data_type = $property_field_property->getDataType();

              $property_label_hint_parts[] = $property_field_name;
              $field_label = t('@label (@property_name)', ['@property_name' => implode(':', $property_label_hint_parts)] + $field_label->getArguments());

              // Prepare views field name.
              $views_field_name = implode($this->fieldSeparator, $views_field_name_parts);
              // Record field usage across index names.
              foreach ($index_instance_index_names as $index_name) {
                $field_instances[$views_field_name][$property_field_data_type]['index_name'][] = $index_name;
              }
              $field_instances[$views_field_name][$property_field_data_type]['label'][] = $field_label;
              // Mark this field as being multi-field.
              $field_instances[$views_field_name][$property_field_data_type]['multi_field'][] = TRUE;
              $field_instances[$views_field_name][$property_field_data_type]['views_options'] = $property_item->getViewsOptions();
            }
          }
        }
      }
      catch (\Exception $e) {
        watchdog_exception('elasticsearch_helper_content', $e);
      }
    }

    // Loop over prepared field instance array.
    foreach ($field_instances as $field_name => $field_name_instance) {
      $field_name_parts = explode($this->fieldSeparator, $field_name);

      foreach ($field_name_instance as $data_type => $field_instance) {
        $index_label = implode(', ', array_unique($field_instance['label']));
        $field = [];

        // Multi-fields are only useful in filter contexts (only filtering).
        if (empty($field_instance['multi_field'])) {
          $field = [
            'title' => $index_label,
            // @todo Change the field plugin to type-specific.
            'id' => $field_instance['views_options']['handlers']['field']['id'],
            // Inner field names in Elasticsearch are referenced with dots.
            'source_field_override' => implode('.', $field_name_parts),
          ] + $field_instance['views_options']['handlers']['field'];
        }

        $filter = [
          'title' => $index_label,
          // @todo Change the filter plugin to type-specific.
          'id' => $field_instance['views_options']['handlers']['filter']['id'],
        ] + $field_instance['views_options']['handlers']['filter'];

        $data['elasticsearch_result'][implode('_', $field_name_parts) . '_' . $data_type] = [
          'title' => $index_label,
          'field' => $field,
          'filter' => $filter,
          'help' => t('Appears in: <small><code>@indices</code></small> with type <small><code>@data_type</code></small>.', [
            '@indices' => implode(', ', $field_instance['index_name']),
            '@data_type' => $data_type,
          ]),
          'real field' => implode('.', $field_name_parts),
        ];
      }
    }

    $data['elasticsearch_result']['elasticsearch_index'] = [
      'title' => t('Elasticsearch index'),
      'filter' => [
        'title' => t('Elasticsearch index'),
        'id' => 'elasticsearch_content_index',
        'indices' => $index_names_all,
      ],
      'help' => t('Elasticsearch content index filter.'),
    ];

    return $data;
  }

  /**
   * {@inheritdoc}
   */
  public function getViewsTableForEntityType(EntityTypeInterface $entity_type) {
    return 'elasticsearch_result';
  }

}
