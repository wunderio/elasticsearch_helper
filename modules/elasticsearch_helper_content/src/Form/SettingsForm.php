<?php

namespace Drupal\elasticsearch_helper_content\Form;

use Drupal\Component\Plugin\Exception\PluginNotFoundException;
use Drupal\Component\Render\FormattableMarkup;
use Drupal\Component\Utility\Html;
use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Entity\ContentEntityTypeInterface;
use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Entity\EntityTypeBundleInfoInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Form\SubformState;
use Drupal\Core\Render\Markup;
use Drupal\elasticsearch_helper_content\ElasticsearchEntityFieldNormalizerInterface;
use Drupal\elasticsearch_helper_content\ContentIndexInterface;
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
    $form = [
      '#attached' => [
        'library' => [
          'elasticsearch_helper_content/admin',
        ],
      ],
      '#attributes' => [
        'class' => 'elasticsearch-helper-content-settings-form',
      ],
      '#after_build' => ['::afterBuild'],
    ];

    $triggering_element = $form_state->getTriggeringElement();

    if (isset($triggering_element)) {
      $index_configuration = $form_state->get('index_configuration');
    }
    else {
      $index_configuration = $this->contentIndex->getIndexConfiguration();
      // Add index parameter.
      foreach ($index_configuration as $entity_type_id => $bundle_configurations) {
        foreach ($bundle_configurations as $bundle => $bundle_configuration) {
          $index_configuration[$entity_type_id][$bundle]['index'] = 1;
        }
      }
      $form_state->set('index_configuration', $index_configuration);
    }

    // Get bundle info.
    $bundles_info = $this->entityTypeBundleInfo->getAllBundleInfo();

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

    // Get entity normalizer definitions.
    $entity_normalizer_definitions = $this->elasticsearchEntityNormalizerManager->getDefinitions();

    asort($entity_type_labels);

    $form['entity_types'] = [
      '#title' => $this->t('Elasticsearch index settings'),
      '#type' => 'checkboxes',
      '#options' => $entity_type_labels,
      '#default_value' => array_keys($index_configuration),
    ];

    $form['settings'] = ['#tree' => TRUE];

    // Loop through sorted entity types.
    foreach ($entity_type_labels as $entity_type_id => $entity_type_label) {
      $wrapper_id = Html::getId("content-type-{$entity_type_id}-wrapper");

      $ajax_attribute = [
        'callback' => '::submitAjax',
        'wrapper' => $wrapper_id,
        'progress' => [
          'type' => 'throbber',
          'message' => NULL,
        ],
      ];

      $form['settings'][$entity_type_id] = [
        '#type' => 'container',
        '#states' => [
          'visible' => [
            ':input[name="entity_types[' . $entity_type_id . ']"]' => ['checked' => TRUE],
          ],
        ],
      ];

      $form['settings'][$entity_type_id]['settings'] = [
        '#type' => 'table',
        '#responsive' => FALSE,
        '#caption' => new FormattableMarkup('<h4>@caption</h4>', ['@caption' => $entity_type_label]),
        '#header' => [
          [
            'data' => t('Index'),
            'class' => ['index'],
          ],
          [
            'data' => t('Normalizer'),
            'class' => ['normalizer'],
          ],
          [
            'data' => t('Settings'),
            'class' => ['settings'],
          ],
        ],
        '#attributes' => [
          'id' => $wrapper_id,
        ],
      ];

      // Loop over entity type bundles.
      foreach ($bundles_info[$entity_type_id] as $bundle => $bundle_info) {
        $bundle_configuration = isset($index_configuration[$entity_type_id][$bundle]) ? $index_configuration[$entity_type_id][$bundle] : [];
        $form_bundle_row = &$form['settings'][$entity_type_id]['settings'][$bundle];
        $row_id = [$entity_type_id, $bundle];

        // Prepare bundle index and normalizer values.
        // If there's a triggering element, attempt to retrieve the
        // submitted value. Otherwise use the stored configuration value or
        // first available normalizer.
        $bundle_index = !empty($bundle_configuration['index']);

        $form_bundle_row['index'] = [
          '#type' => 'checkbox',
          '#title' => $bundle_info['label'],
          '#default_value' => $bundle_index,
          '#disabled' => empty($entity_normalizer_definitions),
          '#row_id' => $row_id,
          '#ajax' => $ajax_attribute,
        ];

        $form_bundle_row['normalizer'] = [];
        $form_bundle_row['settings'] = [];

        if ($bundle_index) {
          $bundle_normalizer = !empty($bundle_configuration['normalizer']) ? $bundle_configuration['normalizer'] : key($entity_normalizer_definitions);

          $form_bundle_row['normalizer'] = [
            '#type' => 'select',
            '#options' => array_map(function ($definition) {
              return $definition['label'];
            }, $entity_normalizer_definitions),
            '#default_value' => $bundle_normalizer,
            '#access' => $bundle_index && !empty($entity_normalizer_definitions),
            '#row_id' => $row_id,
            '#ajax' => $ajax_attribute,
            '#submit' => ['::multistepSubmit'],
          ];

          try {
            // Create normalizer instance.
            $normalizer_configuration = isset($bundle_configuration['configuration']) ? $bundle_configuration['configuration'] : [];
            $normalizer_configuration += [
              'entity_type' => $entity_type_id,
              'bundle' => $bundle,
            ];
            $bundle_normalizer_instance = $this->createNormalizerInstance($bundle_normalizer, $normalizer_configuration);

            // Store normalizer instance in form state.
            $form_state->set(['normalizer', $entity_type_id, $bundle], $bundle_normalizer_instance);

            // Prepare the subform.
            $form_bundle_row['settings']['configuration'] = [];
            $subform_state = SubformState::createForSubform($form_bundle_row['settings']['configuration'], $form, $form_state);
            $configuration_form = $bundle_normalizer_instance->buildConfigurationForm([], $subform_state);

            if ($configuration_form) {
              $row_id_edit = $form_state->get('row_id_edit') ? $form_state->get('row_id_edit') : [];

              if ($row_id_edit && strpos(implode('][', $row_id_edit), implode('][', $row_id)) === 0) {
                // Normalizer value will be stored in
                // $form_bundle_row['settings']['normalizer'].
                unset($form_bundle_row['normalizer']);

                $form_bundle_row['settings'] = [
                  // When settings form is expanded, the cell should span across two
                  // columns (hence colspan 2). In that case "normalizer" cell element
                  // cannot be used in the form as a separate element as it would
                  // create a table cell for it even with an empty value.
                  // The workaround is to add is it any visible element and provide
                  // #parents so that "normalizer" value would end up in proper
                  // place in submitted form state values.
                  'normalizer' => [
                    '#type' => 'value',
                    '#value' => $bundle_normalizer,
                    '#parents' => ['settings', $entity_type_id, 'settings', $bundle, 'normalizer'],
                  ],
                  '#type' => 'container',
                  'configuration' => $configuration_form,
                  'actions' => [
                    '#type' => 'actions',
                    'save_settings' => [
                      '#type' => 'submit',
                      '#value' => t('Update'),
                      '#name' => implode(':', $row_id) . '_update',
                      '#op' => 'update',
                      '#row_id' => $row_id,
                      '#ajax' => $ajax_attribute,
                      '#submit' => ['::multistepSubmit'],
                    ],
                    'cancel_settings' => [
                      '#type' => 'submit',
                      '#value' => t('Cancel'),
                      '#name' => implode(':', $row_id) . '_cancel',
                      '#op' => 'cancel',
                      '#row_id' => $row_id,
                      '#ajax' => $ajax_attribute,
                      '#submit' => ['::multistepSubmit'],
                    ],
                  ],
                  '#wrapper_attributes' => [
                    'colspan' => 2,
                  ],
                ];
              }
              else {
                $form_bundle_row['settings'] = [
                  '#type' => 'image_button',
                  '#src' => 'core/misc/icons/787878/cog.svg',
                  '#attributes' => ['alt' => t('Edit')],
                  '#name' => implode(':', $row_id) . '_edit',
                  '#return_value' => t('Configure'),
                  '#op' => 'edit',
                  '#row_id' => $row_id,
                  '#ajax' => $ajax_attribute,
                  '#submit' => ['::multistepSubmit'],
                ];
              }
            }
          } catch (\Exception $e) {
          }
        }
      }
    }

    $form['actions']['#type'] = 'actions';
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => t('Save configuration'),
      '#button_type' => 'primary',
    ];

    return $form;
  }

  /**
   * Runs after form build.
   *
   * @param array $element
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *
   * @return array
   */
  public function afterBuild(array $element, FormStateInterface $form_state) {
    // Rebuild the configuration if #after_build is being called as part of a
    // form rebuild, i.e. if we are processing input.
    if ($form_state->isProcessingInput()) {
      $this->copyFormValuesToIndexConfiguration($form_state);
    }

    return $element;
  }

  /**
   * Updates index configuration with values from the form.
   *
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   */
  protected function copyFormValuesToIndexConfiguration(FormStateInterface $form_state) {
    $index_configuration = [];

    foreach (array_filter($form_state->getValue('entity_types')) as $entity_type_id) {
      $bundle_configurations = $form_state->getValue(['settings', $entity_type_id, 'settings'], []);
      $bundle_configurations = array_filter($bundle_configurations, function ($configuration) {
        return !empty($configuration['index']);
      });

      foreach ($bundle_configurations as $bundle => $bundle_configuration) {
        /** @var \Drupal\elasticsearch_helper_content\ElasticsearchNormalizerInterface $normalizer */
        $normalizer = $form_state->get(['normalizer', $entity_type_id, $bundle]);

        $index_configuration[$entity_type_id][$bundle] = [
          'index' => $bundle_configuration['index'],
          'normalizer' => $bundle_configuration['normalizer'],
          // Make sure that configuration comes from the right normalizer.
          'configuration' => $normalizer && $normalizer->getPluginId() == $bundle_configuration['normalizer'] ? $normalizer->getConfiguration() : [],
        ];
      }
    }

    $form_state->set('index_configuration', $index_configuration);
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
    $row_id = $trigger['#row_id'];
    list($entity_type_id) = $row_id;

    return $form['settings'][$entity_type_id];
  }

  /**
   * Form element change submit handler.
   *
   * @param $form
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   */
  public function multistepSubmit($form, FormStateInterface $form_state) {
    $trigger = $form_state->getTriggeringElement();
    $op = $trigger['#op'];
    $row_id = $trigger['#row_id'];
    list($entity_type_id, $bundle) = $row_id;

    switch ($op) {
      case 'edit':
        $form_state->set('row_id_edit', $row_id);
        break;

      case 'update':
        array_pop($row_id);
        $form_state->set('row_id_edit', $row_id);

        // Prepare the subform state.
        if ($bundle_normalizer_instance = $form_state->get(['normalizer', $entity_type_id, $bundle])) {
          $subform = &NestedArray::getValue($form, ['settings', $entity_type_id, 'settings', $bundle, 'settings', 'configuration']);
          $subform_state = SubformState::createForSubform($subform, $form, $form_state);
          $bundle_normalizer_instance->submitConfigurationForm($subform, $subform_state);

          $this->copyFormValuesToIndexConfiguration($form_state);
        }

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
   * @param $plugin_id
   * @param array $configuration
   *
   * @return object
   * @throws \Drupal\Component\Plugin\Exception\PluginException
   */
  public function createNormalizerInstance($plugin_id, array $configuration = []) {
    return $this->elasticsearchEntityNormalizerManager->createInstance($plugin_id, $configuration);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    if ($index_configuration = $form_state->get('index_configuration')) {
      foreach ($index_configuration as &$bundle_configurations) {
        $bundle_configurations = array_filter($bundle_configurations, function ($configuration) {
          return !empty($configuration['index']);
        });
        $bundle_configurations = array_map(function ($configuration) {
          unset($configuration['index']);
          return $configuration;
        }, $bundle_configurations);
      }

      $index_configuration = array_filter($index_configuration);
    }

    // Save index configuration.
    $this->configFactory()->getEditable($this->contentIndex->getConfigName())->setData($index_configuration)->save();

    drupal_set_message($this->t('Settings successfully updated.'));
    \Drupal::service('cache.discovery')->delete('elasticsearch_helper_elasticsearch_index_plugins');
  }

}
