<?php

namespace Drupal\elasticsearch_helper_content\Form;

use Drupal\Component\Plugin\Exception\PluginNotFoundException;
use Drupal\Component\Utility\Html;
use Drupal\Core\Entity\ContentEntityTypeInterface;
use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Entity\EntityTypeBundleInfoInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\elasticsearch_helper_content\ElasticsearchEntityFieldNormalizerInterface;
use Drupal\elasticsearch_helper_content\ContentIndexInterface;
use Drupal\field\Entity\FieldConfig;
use Drupal\elasticsearch_helper_content\ElasticsearchEntityNormalizerManagerInterface;
use Drupal\elasticsearch_helper_content\ElasticsearchFieldNormalizerManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Configure the Elasticsearch helper content settings.
 *
 * @internal
 */
class SettingsForm extends FormBase {

  /**
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * @var \Drupal\Core\Entity\EntityTypeBundleInfoInterface
   */
  protected $entityTypeBundleInfo;

  /**
   * @var \Drupal\Core\Entity\EntityFieldManagerInterface
   */
  protected $entityFieldManager;

  /**
   * @var \Drupal\elasticsearch_helper_content\ElasticsearchEntityNormalizerManagerInterface
   */
  protected $elasticsearchEntityNormalizerManager;

  /**
   * @var \Drupal\elasticsearch_helper_content\ElasticsearchFieldNormalizerManagerInterface
   */
  protected $elasticsearchFieldNormalizerManager;

  /**
   * @var \Drupal\elasticsearch_helper_content\ContentIndexInterface
   */
  protected $contentIndex;

  /**
   * SettingsForm constructor.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   * @param \Drupal\Core\Entity\EntityTypeBundleInfoInterface $entity_type_bundle_info
   * @param \Drupal\Core\Entity\EntityFieldManagerInterface $entity_field_manager
   * @param \Drupal\elasticsearch_helper_content\ElasticsearchEntityNormalizerManagerInterface $elasticsearch_entity_normalizer_manager
   * @param \Drupal\elasticsearch_helper_content\ElasticsearchFieldNormalizerManagerInterface $elasticsearch_field_normalizer_manager
   * @param \Drupal\elasticsearch_helper_content\ContentIndexInterface $content_index
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, EntityTypeBundleInfoInterface $entity_type_bundle_info, EntityFieldManagerInterface $entity_field_manager, ElasticsearchEntityNormalizerManagerInterface $elasticsearch_entity_normalizer_manager, ElasticsearchFieldNormalizerManagerInterface $elasticsearch_field_normalizer_manager, ContentIndexInterface $content_index) {
    $this->entityTypeManager = $entity_type_manager;
    $this->entityTypeBundleInfo = $entity_type_bundle_info;
    $this->entityFieldManager = $entity_field_manager;
    $this->elasticsearchEntityNormalizerManager = $elasticsearch_entity_normalizer_manager;
    $this->elasticsearchFieldNormalizerManager = $elasticsearch_field_normalizer_manager;
    $this->contentIndex = $content_index;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager'),
      $container->get('entity_type.bundle.info'),
      $container->get('entity_field.manager'),
      $container->get('plugin.manager.elasticsearch_entity_normalizer'),
      $container->get('plugin.manager.elasticsearch_field_normalizer'),
      $container->get('elasticsearch_helper_content.content_index')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'elasticsearch_helper_content_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $index_configuration = $this->contentIndex->getConfiguration();
    $bundles_info = $this->entityTypeBundleInfo->getAllBundleInfo();
    $triggering_element = $form_state->getTriggeringElement();

    // Get all content type entity types with at least one bundle.
    $entity_types = $this->entityTypeManager->getDefinitions();
    $entity_types = array_filter($entity_types, function($entity_type) use ($bundles_info) {
      return $entity_type instanceof ContentEntityTypeInterface && isset($bundles_info[$entity_type->id()]);
    });

    // Prepare entity type labels.
    $entity_type_labels = array_map(function($entity_type) {
      /** @var \Drupal\Core\Entity\EntityTypeInterface $entity_type */
      return $entity_type->getLabel();
    }, $entity_types);

    // Prepare selected entity types.
    $entity_type_default_value = array_keys($index_configuration);

    asort($entity_type_labels);

    $form = [
      '#labels' => $entity_type_labels,
      '#attached' => [
        'library' => [
          'elasticsearch_helper_content/admin',
        ],
      ],
      '#attributes' => [
        'class' => 'elasticsearch-helper-content-settings-form',
      ],
    ];

    $form['entity_types'] = [
      '#title' => $this->t('Elasticsearch index settings'),
      '#type' => 'checkboxes',
      '#options' => $entity_type_labels,
      '#default_value' => $entity_type_default_value,
    ];

    $form['settings'] = ['#tree' => TRUE];

    // Loop through sorted entity types.
    foreach ($entity_type_labels as $entity_type_id => $entity_type_label) {
      $entity_type = $entity_types[$entity_type_id];

      $form['settings'][$entity_type_id] = [
        '#title' => $entity_type_label,
        '#type' => 'container',
        '#theme' => 'elasticsearch_helper_content_settings_form_table',
        '#bundle_label' => $entity_type->getBundleLabel(),
        '#states' => [
          'visible' => [
            ':input[name="entity_types[' . $entity_type_id . ']"]' => ['checked' => TRUE],
          ],
        ],
        '#attributes' => [
          'id' => [
            Html::getId("content-type-{$entity_type_id}-wrapper"),
          ],
        ],
      ];

      // Loop over entity type bundles.
      foreach ($bundles_info[$entity_type_id] as $bundle => $bundle_info) {
        $bundle_configuration = &$index_configuration[$entity_type_id][$bundle];

        $ajax_attribute = [
          'callback' => '::submitAjax',
          'wrapper' => Html::getId("content-type-{$entity_type_id}-wrapper"),
          'progress' => [
            'type' => 'throbber',
            'message' => NULL,
          ],
        ];

        // Create entity normalizer instances.
        /** @var \Drupal\elasticsearch_helper_content\ElasticsearchNormalizerInterface[] $entity_normalizer_instances */
        $entity_normalizer_instances = array_filter(array_map(function ($definition) {
          try {
           return $this->elasticsearchEntityNormalizerManager->createInstance($definition['id']);
          } catch (PluginNotFoundException $e) {
            return NULL;
          }
        }, $this->elasticsearchEntityNormalizerManager->getDefinitions()));

        // Prepare bundle index and normalizer values.
        // If there's a triggering element, attempt to retrieve the
        // submitted value. Otherwise use the stored configuration value or
        // first available normalizer.
        if (isset($triggering_element)) {
          $parents = ['settings', $entity_type_id, $bundle];
          $bundle_index = $form_state->getValue(array_merge($parents, ['index']));
          $bundle_normalizer = $form_state->getValue(array_merge($parents, ['normalizer']));
        }
        else {
          $bundle_index = !empty($index_configuration[$entity_type_id][$bundle]);
          $bundle_normalizer = !empty($bundle_configuration['normalizer']) ? $bundle_configuration['normalizer'] : key($entity_normalizer_instances);
        }

        $form['settings'][$entity_type_id][$bundle] = [
          'index' => [
            '#type' => 'checkbox',
            '#title' => $bundle_info['label'],
            '#default_value' => $bundle_index,
            '#access' => !empty($entity_normalizer_instances),
            '#entity_type' => $entity_type_id,
            '#ajax' => $ajax_attribute,
          ],
          'normalizer' => [
            '#type' => 'select',
            '#options' => array_map(function ($plugin) {
              /** @var \Drupal\elasticsearch_helper_content\ElasticsearchNormalizerInterface $plugin */
              return $plugin->getPluginDefinition()['label'];
            }, $entity_normalizer_instances),
            '#default_value' => $bundle_normalizer,
            '#attributes' => [
              'data-entity-field-normalizers' => array_keys(array_filter($entity_normalizer_instances, function($plugin) {
                return $plugin instanceof ElasticsearchEntityFieldNormalizerInterface;
              })),
            ],
            '#access' => $bundle_index && !empty($entity_normalizer_instances),
            '#entity_type' => $entity_type_id,
            '#ajax' => $ajax_attribute,
          ],
        ];

        if ($bundle_index && isset($entity_normalizer_instances[$bundle_normalizer]) && $entity_normalizer_instances[$bundle_normalizer] instanceof ElasticsearchEntityFieldNormalizerInterface) {
          // Prepare fields element.
          $form['settings'][$entity_type_id][$bundle]['fields'] = [];

          // Get bundle fields.
          $fields_definitions = $this->entityFieldManager->getFieldDefinitions($entity_type_id, $bundle);
          // Filter out base fields.
          $fields_definitions = array_filter($fields_definitions, function($field) {
            return $field instanceof FieldConfig;
          });

          // Loop over fields.
          foreach ($fields_definitions as $field_name => $field) {
            // Get field type.
            $field_type = $fields_definitions[$field_name]->getType();

            // Get field normalizer definitions.
            /** @var array $field_normalizer_definitions */
            $field_normalizer_definitions = $this->elasticsearchFieldNormalizerManager->getDefinitionsByFieldType($field_type);

            // Prepare entity normalizer.
            // If there's a triggering element, attempt to retrieve the
            // submitted value. Otherwise use the stored configuration value or
            // first available normalizer.
            if (isset($triggering_element)) {
              $parents = ['settings', $entity_type_id, $bundle, 'fields', $field_name];
              $field_index = $form_state->getValue(array_merge($parents, ['index']));
              $field_normalizer = $form_state->getValue(array_merge($parents, ['normalizer']));
            }
            else {
              $field_index = !empty($bundle_configuration['fields'][$field_name]);
              $field_normalizer = !empty($bundle_configuration['fields'][$field_name]['normalizer']) ? $bundle_configuration['fields'][$field_name]['normalizer'] : key($field_normalizer_definitions);
            }

            $form['settings'][$entity_type_id][$bundle]['fields'][$field_name] = [
              'index' => [
                '#type' => 'checkbox',
                '#title' => $field->getLabel(),
                '#default_value' => $field_index,
                '#access' => !empty($field_normalizer_definitions),
                '#entity_type' => $entity_type_id,
                '#ajax' => $ajax_attribute,
              ],
              'normalizer' => [
                '#type' => 'select',
                '#options' => array_map(function ($plugin) {
                  return $plugin['label'];
                }, $field_normalizer_definitions),
                '#default_value' => $field_normalizer,
                '#access' => $field_index && !empty($field_normalizer_definitions),
              ],
            ];
          }
        }
      }
    }

    $form['actions']['#type'] = 'actions';
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Save configuration'),
      '#button_type' => 'primary',
    ];

    return $form;
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
    $trigger = $form_state->getTriggeringElement();
    $entity_type_id = $trigger['#entity_type'];

    return $form['settings'][$entity_type_id];
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $index_settings = [];
    $entity_types = array_filter($form_state->getValue('entity_types'));

    foreach ($entity_types as $entity_type) {
      $bundle_configurations = $form_state->getValue(['settings', $entity_type], []);
      // Filter out only indexable items.
      $bundle_configurations = array_filter($bundle_configurations, [$this, 'filterIndexableItems']);

      foreach ($bundle_configurations as $bundle => $bundle_configuration) {
        try {
          if ($entity_normalizer = $this->elasticsearchEntityNormalizerManager->createInstance($bundle_configuration['normalizer'])) {
            // Save the bundle normalizer.
            $index_settings[$entity_type][$bundle]['normalizer'] = $bundle_configuration['normalizer'];

            // If entity normalizer supports fields, loop over field configuration.
            if ($entity_normalizer instanceof ElasticsearchEntityFieldNormalizerInterface) {
              // Filter out only indexable items.
              $field_configurations = array_filter($bundle_configuration['fields'], [$this, 'filterIndexableItems']);

              foreach ($field_configurations as $field_name => $field_configuration) {
                $index_settings[$entity_type][$bundle]['fields'][$field_name]['normalizer'] = $field_configuration['normalizer'];
              }
            }
          }
        } catch (\Exception $e) {
        }
      }
    }

    $this->configFactory()->getEditable($this->contentIndex->getConfigName())->setData($index_settings)->save();
    drupal_set_message($this->t('Settings successfully updated.'));
  }

  /**
   * Returns items where normalizer is set.
   *
   * @param $item
   *
   * @return bool
   */
  protected function filterIndexableItems($item) {
    return !empty($item['index']);
  }

}
