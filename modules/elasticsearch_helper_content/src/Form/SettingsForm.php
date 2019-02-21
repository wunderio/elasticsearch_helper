<?php

namespace Drupal\elasticsearch_helper_content\Form;

use Drupal\Core\Entity\ContentEntityTypeInterface;
use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Entity\EntityTypeBundleInfoInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\field\Entity\FieldConfig;
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
   * SettingsForm constructor.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   * @param \Drupal\Core\Entity\EntityTypeBundleInfoInterface $entity_type_bundle_info
   * @param \Drupal\Core\Entity\EntityFieldManagerInterface $entity_field_manager
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, EntityTypeBundleInfoInterface $entity_type_bundle_info, EntityFieldManagerInterface $entity_field_manager) {
    $this->entityTypeManager = $entity_type_manager;
    $this->entityTypeBundleInfo = $entity_type_bundle_info;
    $this->entityFieldManager = $entity_field_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager'),
      $container->get('entity_type.bundle.info'),
      $container->get('entity_field.manager')
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
    $index_configuration = $this->config('elasticsearch_helper_content.index')->get();
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
      ];

      // Loop over entity type bundles.
      foreach ($bundles_info[$entity_type_id] as $bundle => $bundle_info) {
        // Get bundle fields.
        $fields = $this->entityFieldManager->getFieldDefinitions($entity_type_id, $bundle);
        // Filter out base fields.
        $fields = array_filter($fields, function($field) {
          return $field instanceof FieldConfig;
        });

        $form['settings'][$entity_type_id][$bundle] = [
          'index' => [
            '#type' => 'checkbox',
            '#title' => $bundle_info['label'],
            '#default_value' => !empty($index_configuration[$entity_type_id][$bundle]),
            '#attributes' => [
              'class' => ['index'],
            ],
          ],
          'normalizer' => [
            '#type' => 'select',
            '#options' => [
              // Display field normalizer options.
            ],
            '#default_value' => !empty($index_configuration[$entity_type_id][$bundle]),
            '#attributes' => [
              'class' => ['normalizer'],
            ],
            '#states' => [
              'visible' => [
                ':input[name="settings[' . $entity_type_id . '][' . $bundle . '][index]"]' => ['checked' => TRUE],
              ],
            ],
          ],
        ];

        $form['settings'][$entity_type_id][$bundle]['fields'] = [];
        foreach ($fields as $field_name => $field) {
          $form['settings'][$entity_type_id][$bundle]['fields'][$field_name] = [
            'index' => [
              '#type' => 'checkbox',
              '#title' => $field->getLabel(),
              '#default_value' => !empty($index_configuration[$entity_type_id][$bundle][$field_name]),
              '#attributes' => [
                'class' => ['index'],
              ],
            ],
            'normalizer' => [
              '#type' => 'select',
              '#options' => [
                // Display field normalizer options.
              ],
              '#default_value' => !empty($index_configuration[$entity_type_id][$bundle]),
              '#attributes' => [
                'class' => ['normalizer'],
              ],
              '#states' => [
                'visible' => [
                  ':input[name="settings[' . $entity_type_id . '][' . $bundle . '][fields][' . $field_name . '][index]"]' => ['checked' => TRUE],
                ],
              ],
            ],
          ];
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
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    drupal_set_message($this->t('Settings successfully updated.'));
  }

}
