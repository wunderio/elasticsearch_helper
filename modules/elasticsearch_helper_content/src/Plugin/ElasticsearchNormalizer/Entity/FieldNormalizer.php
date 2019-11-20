<?php

namespace Drupal\elasticsearch_helper_content\Plugin\ElasticsearchNormalizer\Entity;

use Drupal\Component\Render\FormattableMarkup;
use Drupal\Component\Utility\Html;
use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Form\SubformState;
use Drupal\elasticsearch_helper_content\ElasticsearchEntityNormalizerBase;
use Drupal\elasticsearch_helper_content\ElasticsearchExtraFieldManager;
use Drupal\elasticsearch_helper_content\ElasticsearchField;
use Drupal\elasticsearch_helper_content\ElasticsearchFieldNormalizerManagerInterface;
use Drupal\elasticsearch_helper_content\ElasticsearchNormalizerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * @ElasticsearchEntityNormalizer(
 *   id = "field",
 *   label = @Translation("Content entity field"),
 *   weight = 0
 * )
 */
class FieldNormalizer extends ElasticsearchEntityNormalizerBase {

  /**
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * @var \Drupal\Core\Entity\EntityFieldManagerInterface
   */
  protected $entityFieldManager;

  /**
   * @var \Drupal\elasticsearch_helper_content\ElasticsearchExtraFieldManager
   */
  protected $elasticsearchExtraFieldManager;

  /**
   * @var \Drupal\elasticsearch_helper_content\ElasticsearchFieldNormalizerManagerInterface
   */
  protected $elasticsearchFieldNormalizerManager;

  /**
   * ElasticsearchEntityFieldNormalizer constructor.
   *
   * @param array $configuration
   * @param $plugin_id
   * @param $plugin_definition
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   * @param \Drupal\Core\Entity\EntityFieldManagerInterface $entity_field_manager
   * @param \Drupal\elasticsearch_helper_content\ElasticsearchFieldNormalizerManagerInterface $elasticsearch_field_normalizer_manager
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, EntityTypeManagerInterface $entity_type_manager, EntityFieldManagerInterface $entity_field_manager, ElasticsearchExtraFieldManager $elasticsearch_extra_field_manager, ElasticsearchFieldNormalizerManagerInterface $elasticsearch_field_normalizer_manager) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);

    $this->entityTypeManager = $entity_type_manager;
    $this->entityFieldManager = $entity_field_manager;
    $this->elasticsearchExtraFieldManager = $elasticsearch_extra_field_manager;
    $this->elasticsearchFieldNormalizerManager = $elasticsearch_field_normalizer_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity_type.manager'),
      $container->get('entity_field.manager'),
      $container->get('plugin.manager.elasticsearch_extra_field'),
      $container->get('plugin.manager.elasticsearch_field_normalizer')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'fields' => [],
    ] + parent::defaultConfiguration();
  }

  /**
   * Returns a list of field normalizer instances.
   *
   * @return \Drupal\elasticsearch_helper_content\ElasticsearchNormalizerInterface[]
   */
  protected function getFieldNormalizerInstances() {
    $instances = [];

    try {
      // Get entity keys.
      $entity_type_instance = $this->entityTypeManager->getDefinition($this->targetEntityType);
      $entity_keys = $entity_type_instance->getKeys();

      foreach ($this->configuration['fields'] as $field_name => $field_configuration) {
        // If field name maps to an entity key, use entity key.
        $entity_field_name = isset($entity_keys[$field_name]) ? $entity_keys[$field_name] : $field_name;

        $instances[$field_name] = $this->createFieldNormalizerInstance($field_configuration['normalizer'], $field_configuration['normalizer_configuration'], $entity_field_name);
      }
    }
    catch (\Exception $e) {
      watchdog_exception('elasticsearch_helper_content', $e);
    }

    return $instances;
  }

  /**
   * {@inheritdoc}
   */
  public function normalize($object, array $context = []) {
    $data = parent::normalize($object, $context);
    // Pass entity object down to field normalizers.
    $context['entity'] = $object;

    try {
      // Get entity type instance.
      $entity_type = $this->entityTypeManager->getDefinition($object->getEntityTypeId());

      foreach ($this->getFieldNormalizerInstances() as $field_name => $field_normalizer_instance) {
        // Convert field name if it's it's an entity key.
        $entity_field_name = $entity_type->getKey($field_name) ?: $field_name;
        // Set default field item list instance.
        $field = NULL;

        if ($object->hasField($entity_field_name)) {
          $field = $object->get($entity_field_name);
        }

        $data[$field_name] = $field_normalizer_instance->normalize($field, $context);
      }
    }
    catch (\Exception $e) {
      watchdog_exception('elasticsearch_helper_content', $e);
    }

    return $data;
  }

  /**
   * {@inheritdoc}
   */
  public function getPropertyDefinitions() {
    // Get core property definitions.
    $core_property_definitions = $this->getCorePropertyDefinitions();

    // Prepare property (field) definitions.
    $property_definitions = [];

    foreach ($this->getFieldNormalizerInstances() as $field_name => $field_normalizer_instance) {
      $property_definitions[$field_name] = $field_normalizer_instance->getPropertyDefinitions();
    }

    return array_merge($core_property_definitions, $property_definitions);
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $entity_type_id = $this->targetEntityType;
    $bundle = $this->targetBundle;

    $triggering_element = $form_state->getTriggeringElement();

    // Every form element on this form has "#row_id" attribute.
    if ($triggering_element && isset($triggering_element['#row_id'])) {
      $parent_offset = isset($triggering_element['#parent_offset']) ? $triggering_element['#parent_offset'] : NULL;
      $form_parents = $this->getParentsArray($triggering_element['#parents'], $parent_offset);
      $field_configurations = NestedArray::getValue($form_state->getUserInput(), $form_parents);
    }
    else {
      $field_configurations = array_map(function ($field) {
        // Add "index" element so that checkbox is checked.
        $field['index'] = 1;
        return $field;
      }, $this->configuration['fields']);
    }

    if (!isset($entity_type_id, $bundle)) {
      return [];
    }

    $entity_type_instance = $this->entityTypeManager->getDefinition($entity_type_id);
    $flipped_entity_keys = array_flip($entity_type_instance->getKeys());

    // Get bundle fields.
    $fields_definitions = $this->entityFieldManager->getFieldDefinitions($entity_type_id, $bundle);

    /** @var \Drupal\elasticsearch_helper_content\ElasticsearchField[] $fields */
    $fields = array_map(function (FieldDefinitionInterface $field_definition) {
      return ElasticsearchField::createFromFieldDefinition($field_definition);
    }, $fields_definitions);

    // Gather extra fields from Elasticsearch extra field plugins.
    $extra_fields = $this->elasticsearchExtraFieldManager->getExtraFields();
    // Merge extra fields with base fields.
    $fields = array_merge($fields, $extra_fields);

    $table_id = Html::getId('elasticsearch-entity-field-normalizer-form');

    $form = $form + [
      'fields' => [
        '#type' => 'table',
        '#title' => t('Title'),
        '#header' => [t('Field'), t('Normalizer'), t('Settings')],
        '#attributes' => [
          'id' => $table_id,
        ],
      ],
    ];

    $ajax_attribute = [
      'callback' => [$this, 'submitAjax'],
      'wrapper' => $table_id,
      'progress' => [
        'type' => 'throbber',
        'message' => NULL,
      ],
    ];

    $core_property_definition_keys = array_keys($this->getCorePropertyDefinitions());

    // Loop over fields.
    foreach ($fields as $entity_field_name => $field) {
      // If field name maps to an entity key, use entity key.
      $field_name = isset($flipped_entity_keys[$entity_field_name]) ? $flipped_entity_keys[$entity_field_name] : $entity_field_name;

      // Do not list fields that are already defined in core property
      // definitions.
      if (!in_array($field_name, $core_property_definition_keys)) {
        // Get field normalizer definitions.
        $field_normalizer_definitions = $this->elasticsearchFieldNormalizerManager->getDefinitionsByFieldType($field->getType());

        // Get field configuration.
        $field_configuration = isset($field_configurations[$field_name]) ? $field_configurations[$field_name] : [];
        $field_configuration += [
          'index' => 0,
          'normalizer' => key($field_normalizer_definitions),
          'normalizer_configuration' => [],
        ];

        $row_id = [$field_name];
        $form_field_row = &$form['fields'][$field_name];

        $field_index = !empty($field_configuration['index']);

        $form_field_row['index'] = [
          '#type' => 'checkbox',
          '#title' => new FormattableMarkup('@field_label <small>(<code>@field_name</code>)</small>', [
            '@field_label' => $field->getLabel(),
            '@field_name' => $field->getName(),
          ]),
          '#default_value' => $field_index,
          '#disabled' => empty($field_normalizer_definitions),
          '#row_id' => $row_id,
          '#ajax' => $ajax_attribute,
        ];

        $form_field_row['normalizer'] = [];
        $form_field_row['settings'] = [];

        if ($field_index) {
          $field_normalizer = $field_configuration['normalizer'];

          $form_field_row['normalizer'] = [
            '#type' => 'select',
            '#options' => array_map(function ($plugin) {
              return $plugin['label'];
            }, $field_normalizer_definitions),
            '#default_value' => $field_normalizer,
            '#access' => !empty($field_normalizer_definitions),
            '#row_id' => $row_id,
            '#ajax' => $ajax_attribute,
            '#submit' => [[$this, 'multistepSubmit']],
          ];

          try {
            $field_normalizer_instance = $this->getStoredFieldNormalizerInstance($field_name, $form_state);

            // Check if normalizer instance is set and if it matches the selected
            // normalizer.
            if (!$this->instanceMatchesPluginId($field_normalizer, $field_normalizer_instance)) {
              $field_normalizer_instance = $this->createFieldNormalizerInstance($field_normalizer, $field_configuration['normalizer_configuration'], $entity_field_name);

              // Store field normalizer instance in form state.
              $form_state->set(['field_normalizer', $field_name], $field_normalizer_instance);
            }

            // Prepare the subform state.
            $configuration_form = [];
            $subform_state = SubformState::createForSubform($configuration_form, $form, $form_state);
            $configuration_form = $field_normalizer_instance->buildConfigurationForm([], $subform_state);

            if ($configuration_form) {
              $row_id_edit = $form_state->get('row_id_edit') ? $form_state->get('row_id_edit') : [];

              if ($row_id_edit && strpos(implode('][', $row_id_edit), implode('][', $row_id)) === 0) {
                $form_field_row['settings'] = [
                  '#type' => 'container',
                  'configuration' => $configuration_form,
                  'actions' => [
                    '#type' => 'actions',
                    'save_settings' => [
                      '#type' => 'submit',
                      '#value' => t('Update'),
                      '#name' => implode(':', $row_id) . '_update',
                      '#op' => 'update',
                      '#submit' => [[$this, 'multistepSubmit']],
                      '#row_id' => $row_id,
                      '#ajax' => $ajax_attribute,
                      '#parent_offset' => -4,
                    ],
                    'cancel_settings' => [
                      '#type' => 'submit',
                      '#value' => t('Cancel'),
                      '#name' => implode(':', $row_id) . '_cancel',
                      '#op' => 'cancel',
                      '#submit' => [[$this, 'multistepSubmit']],
                      '#row_id' => $row_id,
                      '#ajax' => $ajax_attribute,
                      '#parent_offset' => -4,
                    ],
                  ],
                ];
              }
              else {
                $form_field_row['settings'] = [
                  '#type' => 'image_button',
                  '#src' => 'core/misc/icons/787878/cog.svg',
                  '#attributes' => ['alt' => t('Edit')],
                  '#name' => implode(':', $row_id) . '_edit',
                  '#return_value' => t('Configure'),
                  '#op' => 'edit',
                  '#submit' => [[$this, 'multistepSubmit']],
                  '#row_id' => $row_id,
                  '#ajax' => $ajax_attribute,
                ];
              }
            }
          }
          catch (\Exception $e) {
            watchdog_exception('elasticsearch_helper_content', $e);
          }
        }
      }
    }

    return $form;
  }

  /**
   * Returns parents array.
   *
   * Generally form elements of this plugin's configuration form are two
   * levels away from the parent's form, hence -2 is assumed as an offset.
   *
   * @param array $source
   * @param int|null $offset
   *
   * @return array
   */
  protected function getParentsArray(array $source, $offset = NULL) {
    $offset = $offset ?: -2;
    array_splice($source, $offset);

    return $source;
  }

  /**
   * Ajax submit handler.
   *
   * @param $form
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *
   * @return array
   */
  public function submitAjax($form, FormStateInterface $form_state) {
    $triggering_element = $form_state->getTriggeringElement();

    $form_state->setRebuild();

    $parent_offset = isset($triggering_element['#parent_offset']) ? $triggering_element['#parent_offset'] : NULL;
    $form_parents = $this->getParentsArray($triggering_element['#array_parents'], $parent_offset);

    $return_form = NestedArray::getValue($form, $form_parents);

    return $return_form;
  }

  /**
   * Form element change submit handler.
   *
   * @param $form
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   */
  public function multistepSubmit($form, FormStateInterface $form_state) {
    $triggering_element = $form_state->getTriggeringElement();
    $op = $triggering_element['#op'];
    $row_id = $triggering_element['#row_id'];
    list($field_name) = $row_id;

    switch ($op) {
      case 'edit':
        $form_state->set('row_id_edit', $row_id);

        break;

      case 'update':
        if ($field_normalizer_instance = $this->getStoredFieldNormalizerInstance($field_name, $form_state)) {
          // Trigger has the clue to parents array.
          $form_parents = $this->getParentsArray($triggering_element['#array_parents']);
          // Configuration add configuration form parent element.
          $form_parents[] = 'configuration';

          if ($subform = &NestedArray::getValue($form, $form_parents)) {
            $subform_state = SubformState::createForSubform($subform, $form, $form_state);
            $field_normalizer_instance->submitConfigurationForm($subform, $subform_state);
          }
        }

        array_pop($row_id);
        $form_state->set('row_id_edit', $row_id);

        break;

      case 'cancel':
        array_pop($row_id);
        $form_state->set('row_id_edit', $row_id);
        break;

    }

    // Rebuild the form.
    $form_state->setRebuild();
  }

  /**
   * Returns field normalizer instance.
   *
   * @param $normalizer
   * @param array $normalizer_configuration
   * @param $entity_field_name
   *
   * @return \Drupal\elasticsearch_helper_content\ElasticsearchNormalizerInterface
   *
   * @throws \Drupal\Component\Plugin\Exception\PluginException
   */
  protected function createFieldNormalizerInstance($normalizer, array $normalizer_configuration, $entity_field_name) {
    // Explicitly set entity type and bundle. They are unset in field
    // normalizer plugins and are not stored in configuration.
    $normalizer_configuration['entity_type'] = $this->targetEntityType;
    $normalizer_configuration['bundle'] = $this->targetBundle;

    /** @var \Drupal\elasticsearch_helper_content\ElasticsearchNormalizerInterface $result */
    $result = $this->elasticsearchFieldNormalizerManager->createInstance($normalizer, $normalizer_configuration);

    return $result;
  }

  /**
   * Returns field normalizer instance or NULL.
   *
   * @param $field_name
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *
   * @return \Drupal\elasticsearch_helper_content\ElasticsearchNormalizerInterface|null
   */
  protected function getStoredFieldNormalizerInstance($field_name, FormStateInterface $form_state) {
    return $form_state->get(['field_normalizer', $field_name]);
  }

  /**
   * Returns TRUE if normalizer instance plugin ID matches the given plugin ID.
   *
   * @param $plugin_id
   * @param \Drupal\elasticsearch_helper_content\ElasticsearchNormalizerInterface|NULL $instance
   *
   * @return bool
   */
  protected function instanceMatchesPluginId($plugin_id, ElasticsearchNormalizerInterface $instance = NULL) {
    if ($instance && $instance->getPluginId() == $plugin_id) {
      return TRUE;
    }

    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    $configuration = $this->configuration;
    // Reset field settings.
    $configuration['fields'] = [];

    $entity_type_id = $this->targetEntityType;
    $bundle = $this->targetBundle;

    // Filter out unselected fields.
    $fields = array_filter($form_state->getValue('fields', []), function ($item) {
      return !empty($item['index']);
    });

    foreach ($fields as $field_name => $field_configuration) {
      $field_normalizer_configuration = [];

      // Gather configuration from field normalizer instances.
      if ($field_normalizer_instance = $this->getStoredFieldNormalizerInstance($field_name, $form_state)) {
        // Submit all open normalizer forms.
        if ($subform = &NestedArray::getValue($form, ['fields', $field_name, 'settings', 'configuration'])) {
          $subform_state = SubformState::createForSubform($subform, $form, $form_state);
          $field_normalizer_instance->submitConfigurationForm($subform, $subform_state);
        }

        $field_normalizer_configuration = $field_normalizer_instance->getConfiguration();
      }

      $configuration['fields'][$field_name] = [
        'normalizer' => $field_configuration['normalizer'],
        'normalizer_configuration' => $field_normalizer_configuration,
      ];
    }

    $this->configuration = $configuration;
  }

}
