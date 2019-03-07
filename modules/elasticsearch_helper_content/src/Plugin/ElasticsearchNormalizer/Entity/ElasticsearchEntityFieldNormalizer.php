<?php

namespace Drupal\elasticsearch_helper_content\Plugin\ElasticsearchNormalizer\Entity;

use Drupal\Component\Render\FormattableMarkup;
use Drupal\Component\Utility\Html;
use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Entity\EntityTypeBundleInfoInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Form\SubformState;
use Drupal\elasticsearch_helper_content\ElasticsearchEntityNormalizerBase;
use Drupal\elasticsearch_helper_content\ElasticsearchFieldNormalizerManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * @ElasticsearchEntityNormalizer(
 *   id = "field",
 *   label = @Translation("Content entity field"),
 *   weight = 5
 * )
 */
class ElasticsearchEntityFieldNormalizer extends ElasticsearchEntityNormalizerBase {

  /**
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * @var \Drupal\Core\Entity\EntityFieldManagerInterface
   */
  protected $entityFieldManager;

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
   * @param \Drupal\Core\Entity\EntityTypeBundleInfoInterface $entity_type_bundle_info
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   * @param \Drupal\Core\Entity\EntityFieldManagerInterface $entity_field_manager
   * @param \Drupal\elasticsearch_helper_content\ElasticsearchFieldNormalizerManagerInterface $elasticsearch_field_normalizer_manager
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, EntityTypeBundleInfoInterface $entity_type_bundle_info, EntityTypeManagerInterface $entity_type_manager, EntityFieldManagerInterface $entity_field_manager, ElasticsearchFieldNormalizerManagerInterface $elasticsearch_field_normalizer_manager) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $entity_type_bundle_info);

    $this->entityTypeManager = $entity_type_manager;
    $this->entityFieldManager = $entity_field_manager;
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
      $container->get('entity_type.bundle.info'),
      $container->get('entity_type.manager'),
      $container->get('entity_field.manager'),
      $container->get('plugin.manager.elasticsearch_field_normalizer')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'fields' => [],
    ]  + parent::defaultConfiguration();
  }

  /**
   * Returns a list of field normalizer instances.
   *
   * @return \Drupal\elasticsearch_helper_content\ElasticsearchNormalizerInterface[]
   */
  protected function getFieldNormalizerInstances() {
    $instances = [];

    // Get entity keys.
    $entity_type_instance = $this->entityTypeManager->getDefinition($this->configuration['entity_type']);
    $entity_keys = $entity_type_instance->getKeys();

    // Get field definitions.
    $fields_definitions = $this->entityFieldManager->getFieldDefinitions($this->configuration['entity_type'], $this->configuration['bundle']);

    foreach ($this->configuration['fields'] as $field_name => $field_configuration) {
      // If field name maps to an entity key, use entity key.
      $entity_field_name = isset($entity_keys[$field_name]) ? $entity_keys[$field_name] : $field_name;

      try {
        // Prepare configuration.
        $normalizer_configuration = [
          'field_type' => $fields_definitions[$entity_field_name]->getType(),
        ];
        $instances[$field_name] = $this->elasticsearchFieldNormalizerManager->createInstance($field_configuration['normalizer'], $normalizer_configuration);
      } catch (\Exception $e) {
        watchdog_exception('elasticsearch_helper_content', $e);
      }
    }

    return $instances;
  }

  /**
   * {@inheritdoc}
   */
  public function normalize($object, array $context = []) {
    $data = parent::normalize($object, $context);

    foreach ($this->getFieldNormalizerInstances() as $field_name => $field_normalizer_instance) {
      if ($object->hasField($field_name)) {
        $data[$field_name] = $field_normalizer_instance->normalize($object->get($field_name), $context);
      }
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
    $entity_type_id = $this->configuration['entity_type'];
    $bundle = $this->configuration['bundle'];

    if (!isset($entity_type_id, $bundle)) {
      return [];
    }

    $entity_type_instance = $this->entityTypeManager->getDefinition($entity_type_id);
    $flipped_entity_keys = array_flip($entity_type_instance->getKeys());

    // Get bundle fields.
    $fields_definitions = $this->entityFieldManager->getFieldDefinitions($entity_type_id, $bundle);

    $form = $form + [
      'fields' => [
        '#type' => 'table',
        '#title' => t('Title'),
        '#header' => [t('Field'), t('Normalizer'), t('Settings')],
      ],
    ];

    $ajax_attribute = [
      'callback' => '::submitAjax',
      'wrapper' => Html::getId("content-type-{$entity_type_id}-wrapper"),
      'progress' => [
        'type' => 'throbber',
        'message' => NULL,
      ],
    ];

    // Loop over fields.
    foreach ($fields_definitions as $entity_field_name => $field) {
      $row_id = [$entity_type_id, $bundle, $entity_field_name];

      // Get field type.
      $field_type = $fields_definitions[$entity_field_name]->getType();

      // If field name maps to an entity key, use entity key.
      $field_name = isset($flipped_entity_keys[$entity_field_name]) ? $flipped_entity_keys[$entity_field_name] : $entity_field_name;

      // Get field normalizer definitions.
      $field_normalizer_definitions = $this->elasticsearchFieldNormalizerManager->getDefinitionsByFieldType($field_type);

      // Prepare entity normalizer.
      // If there's a triggering element, attempt to retrieve the
      // submitted value. Otherwise use the stored configuration value or
      // first available normalizer.
      $field_index = !empty($this->configuration['fields'][$field_name]);
      $field_normalizer = !empty($this->configuration['fields'][$field_name]['normalizer']) ? $this->configuration['fields'][$field_name]['normalizer'] : NULL;

      try {
        $normalizer_configuration = !empty($this->configuration['fields'][$field_name]['configuration']) ? $this->configuration['fields'][$field_name]['configuration'] : [];
        $field_normalizer_instance = $this->elasticsearchFieldNormalizerManager->createInstance($field_normalizer, $normalizer_configuration);
        // Store normalizer instance in form state.
        $form_state->set(['field_normalizer', $entity_type_id, $bundle, $field_name], $field_normalizer_instance);
      } catch (\Exception $e) {
        watchdog_exception('elasticsearch_helper_content', $e);
      }

      $form['fields'][$field_name]['index'] = [
        '#type' => 'checkbox',
        '#title' => new FormattableMarkup('@field_label <small>(<code>@field_name</code>)</small>', [
          '@field_label' => $field->getLabel(),
          '@field_name' => $field->getName(),
        ]),
        '#default_value' => $field_index,
        '#disabled' => empty($field_normalizer_definitions),
      ];

      $form['fields'][$field_name]['normalizer'] = [
        '#type' => 'select',
        '#options' => array_map(function ($plugin) {
          return $plugin['label'];
        }, $field_normalizer_definitions),
        '#default_value' => $field_normalizer,
        '#access' => !empty($field_normalizer_definitions),
      ];

      $row_id_edit = $form_state->get('row_id_edit') ? $form_state->get('row_id_edit') : [];

      if ($row_id_edit && strpos(implode('][', $row_id_edit), implode('][', $row_id)) === 0) {
        $form['fields'][$field_name]['settings'] = [
          '#type' => 'container',
          'configuration' => [],
          'actions' => [
            '#type' => 'actions',
            'save_settings' => [
              '#type' => 'submit',
              '#value' => t('Update'),
              '#name' => implode(':', $row_id) . '_update',
              '#op' => 'update',
              '#submit' => ['::multistepSubmit'],
              '#row_id' => $row_id,
              '#ajax' => $ajax_attribute,
            ],
            'cancel_settings' => [
              '#type' => 'submit',
              '#value' => t('Cancel'),
              '#name' => implode(':', $row_id) . '_cancel',
              '#op' => 'cancel',
              '#submit' => ['::multistepSubmit'],
              '#row_id' => $row_id,
              '#ajax' => $ajax_attribute,
            ],
          ],
        ];

        if ($field_normalizer_instance) {
          // Prepare the subform state.
          $subform_state = SubformState::createForSubform($form['fields'][$field_name]['settings']['configuration'], $form, $form_state);
          $form['fields'][$field_name]['settings']['configuration'] = $field_normalizer_instance->buildConfigurationForm([], $subform_state);
        }
      }
      else {
        $form['fields'][$field_name]['settings'] = [
          '#type' => 'image_button',
          '#src' => 'core/misc/icons/787878/cog.svg',
          '#attributes' => ['alt' => t('Edit')],
          '#name' => implode(':', $row_id) . '_edit',
          '#return_value' => t('Configure'),
          '#op' => 'edit',
          '#submit' => ['::multistepSubmit'],
          '#row_id' => $row_id,
          '#ajax' => $ajax_attribute,
        ];
      }
    }

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    $configuration = [];
    list($entity_type_id, $bundle) = $form_state->get('row_id_edit');

    $fields = array_filter($form_state->getValue('fields', []), function ($item) {
      return !empty($item['index']);
    });

    foreach ($fields as $field_name => $field_configuration) {
      $field_normalizer_configuration = [];

      try {
        if ($field_normalizer_instance = $form_state->get(['field_normalizer', $entity_type_id, $bundle, $field_name])) {
          $field_normalizer_configuration = $field_normalizer_instance->getConfiguration();
        }
      } catch (\Exception $e) {
      }

      $configuration['fields'][$field_name] = [
        'normalizer' => $field_configuration['normalizer'],
        'configuration' => $field_normalizer_configuration,
      ];
    }

    $this->configuration = $configuration;
  }

}
