<?php

namespace Drupal\elasticsearch_helper_content\Form;

use Drupal\Core\Entity\ContentEntityTypeInterface;
use Drupal\Core\Entity\EntityManagerInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\elasticsearch_helper_content\Entity\ElasticsearchContentSettings;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Configure the elasticsearch helper content settings for this site.
 *
 * @internal
 */
class ElasticsearchContentSettingsForm extends FormBase {

  /**
   * The entity manager.
   *
   * @var \Drupal\Core\Entity\EntityManagerInterface
   */
  protected $entityManager;

  /**
   * ElasticsearchHelperConfigurationForm constructor.
   *
   * @param \Drupal\Core\Entity\EntityManagerInterface $entity_manager
   */
  public function __construct(EntityManagerInterface $entity_manager) {
    $this->entityManager = $entity_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity.manager')
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
    $entity_types = $this->entityManager->getDefinitions();
    $labels = [];
    $default = [];

    $bundles = $this->entityManager->getAllBundleInfo();
    $esindex_configuration = [];
    foreach ($entity_types as $entity_type_id => $entity_type) {
      if (!$entity_type instanceof ContentEntityTypeInterface || !isset($bundles[$entity_type_id])) {
        continue;
      }

      $labels[$entity_type_id] = $entity_type->getLabel() ?: $entity_type_id;
      $default[$entity_type_id] = FALSE;

      // Check whether we have any custom setting.
      foreach ($bundles[$entity_type_id] as $bundle => $bundle_info) {
        $config = ElasticsearchContentSettings::loadByEntityTypeBundle($entity_type_id, $bundle);
        if (!$config->isDefaultConfiguration()) {
          $default[$entity_type_id] = $entity_type_id;
        }
        $esindex_configuration[$entity_type_id][$bundle] = $config;
      }
    }

    asort($labels);

    $form = [
      '#labels' => $labels,
      '#attached' => [
        'library' => [
          'elasticsearch_helper_content/elasticsearch_helper_content.admin',
        ],
      ],
      '#attributes' => [
        'class' => 'elasticsearch-helper-content-settings-form',
      ],
    ];

    $form['entity_types'] = [
      '#title' => $this->t('Elasticsearch index settings'),
      '#type' => 'checkboxes',
      '#options' => $labels,
      '#default_value' => $default,
    ];

    $form['settings'] = ['#tree' => TRUE];

    foreach ($labels as $entity_type_id => $label) {
      $entity_type = $entity_types[$entity_type_id];

      $form['settings'][$entity_type_id] = [
        '#title' => $label,
        '#type' => 'container',
        '#entity_type' => $entity_type_id,
        '#theme' => 'elasticsearch_helper_content_settings_table',
        '#bundle_label' => $entity_type->getBundleLabel() ?: $label,
        '#states' => [
          'visible' => [
            ':input[name="entity_types[' . $entity_type_id . ']"]' => ['checked' => TRUE],
          ],
        ],
      ];

      foreach ($bundles[$entity_type_id] as $bundle => $bundle_info) {
        $form['settings'][$entity_type_id][$bundle]['settings'] = [
          '#type' => 'item',
          '#label' => $bundle_info['label'],
          'esindex' => [
            '#type' => 'elasticsearch_configuration',
            '#entity_information' => [
              'entity_type' => $entity_type_id,
              'bundle' => $bundle,
            ],
            '#default_value' => $esindex_configuration[$entity_type_id][$bundle],
          ],
        ];
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
//    $entity_types = $form_state->getValue('entity_types');
//    foreach ($form_state->getValue('settings') as $entity_type => $entity_settings) {
//      foreach ($entity_settings as $bundle => $bundle_settings) {
//        $config = ElasticsearchIndexSettings::loadByEntityTypeBundle($entity_type, $bundle);
//        if (empty($entity_types[$entity_type])) {
//          $bundle_settings['settings']['language']['language_alterable'] = FALSE;
//        }
//        $config->setDefaultLangcode($bundle_settings['settings']['language']['langcode'])
//          ->setLanguageAlterable($bundle_settings['settings']['language']['language_alterable'])
//          ->save();
//      }
//    }
    drupal_set_message($this->t('Settings successfully updated.'));
  }

}
