<?php

namespace Drupal\elasticsearch_helper_content\Form;

use Drupal\Component\Utility\Html;
use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Entity\ContentEntityTypeInterface;
use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Entity\EntityTypeBundleInfoInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Form\SubformState;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\elasticsearch_helper_content\ElasticsearchEntityNormalizerManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Form handler for the Elasticsearch content index add and edit forms.
 */
class ElasticsearchContentIndexForm extends EntityForm {

  /**
   * @var \Drupal\Core\Entity\EntityTypeBundleInfoInterface
   */
  protected $entityTypeBundleInfo;

  /**
   * @var \Drupal\elasticsearch_helper_content\ElasticsearchEntityNormalizerManagerInterface
   */
  protected $elasticsearchEntityNormalizerManager;

  /**
   * ElasticsearchContentIndexForm constructor.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   * @param \Drupal\Core\Entity\EntityTypeBundleInfoInterface $entity_type_bundle_info
   * @param \Drupal\elasticsearch_helper_content\ElasticsearchEntityNormalizerManagerInterface $elasticsearch_entity_normalizer_manager
   */
  public function __construct(EntityTypeManagerInterface $entityTypeManager, EntityTypeBundleInfoInterface $entity_type_bundle_info, ElasticsearchEntityNormalizerManagerInterface $elasticsearch_entity_normalizer_manager) {
    $this->entityTypeManager = $entityTypeManager;
    $this->entityTypeBundleInfo = $entity_type_bundle_info;
    $this->elasticsearchEntityNormalizerManager = $elasticsearch_entity_normalizer_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager'),
      $container->get('entity_type.bundle.info'),
      $container->get('plugin.manager.elasticsearch_entity_normalizer')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);

    $form_id = Html::getId('elasticsearch-content-index-form');
    $form['#attributes']['id'] = $form_id;

    $ajax_attribute = [
      'callback' => [$this, 'reloadForm'],
      'wrapper' => $form_id,
    ];

    /** @var \Drupal\elasticsearch_helper_content\ElasticsearchContentIndexInterface $index */
    $index = $this->entity;

    $form['label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Index label'),
      '#maxlength' => 255,
      '#default_value' => $index->label(),
      '#required' => TRUE,
    ];

    $form['id'] = [
      '#type' => 'machine_name',
      '#default_value' => $index->id(),
      '#machine_name' => [
        'exists' => '\Drupal\elasticsearch_helper_content\Entity\ElasticsearchContentIndex::load',
      ],
      '#disabled' => !$index->isNew(),
    ];

    $index_name_description = $this->t('Index name must contain only lowercase letters, numbers, hyphens and underscores.');

    $form['index_name'] = [
      '#type' => 'machine_name',
      '#title' => $this->t('Index name'),
      '#description' => $index_name_description,
      '#default_value' => $index->getIndexName(),
      '#machine_name' => [
        'exists' => [$this, 'indexNameExists'],
        // Elasticsearch index name must:
        //   - start with alphanumeric characters
        //   - be lowercase
        //   - contain alphanumeric characters (except hyphens and underscores)
        'replace_pattern' => '^[^a-z0-9]|[^a-z0-9_-]+',
        'error' => $index_name_description,
      ],
      // '#disabled' => !$index->isNew(),
    ];

    $form['multilingual'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Multilingual'),
      '#description' => t('Check if this index should support multiple languages.'),
      '#default_value' => $index->isMultilingual(),
    ];

    // Get bundle info.
    $bundles_info = $this->entityTypeBundleInfo->getAllBundleInfo();

    // Get all content type entity types with at least one bundle.
    $entity_types = $this->entityTypeManager->getDefinitions();
    $entity_types = array_filter($entity_types, function ($entity_type) use ($bundles_info) {
      return $entity_type instanceof ContentEntityTypeInterface && isset($bundles_info[$entity_type->id()]);
    });

    // Prepare entity type labels.
    $entity_type_options = array_map(function ($entity_type) {
      /** @var \Drupal\Core\Entity\EntityTypeInterface $entity_type */
      return $entity_type->getLabel();
    }, $entity_types);

    $target_entity_type = $index->getTargetEntityType();

    $form['entity_type'] = [
      '#type' => 'select',
      '#title' => $this->t('Entity type'),
      '#description' => $this->t('Select entity type for the index.'),
      '#options' => $entity_type_options,
      '#default_value' => $target_entity_type,
      '#required' => TRUE,
      '#ajax' => $ajax_attribute,
    ];

    $bundle_options = array_map(function ($bundle) {
      return $bundle['label'];
    }, isset($bundles_info[$target_entity_type]) ? $bundles_info[$target_entity_type] : []);

    // Explicitly set target bundle to enable normalizer options when
    // entity type is selected.
    if (!($target_bundle = $index->getTargetBundle())) {
      $target_bundle = key($bundle_options);
      $index->setTargetBundle($target_bundle);
    }

    $form['bundle'] = [
      '#type' => 'select',
      '#title' => $this->t('Bundle'),
      '#description' => $this->t('Select bundle for the index.'),
      '#options' => $bundle_options,
      '#default_value' => $target_bundle,
      '#required' => TRUE,
      '#ajax' => $ajax_attribute,
    ];

    // Get entity normalizer definitions.
    $entity_normalizer_definitions = $this->elasticsearchEntityNormalizerManager->getDefinitions();

    $normalizer = $index->getNormalizer();

    $form['normalizer'] = [
      '#type' => 'select',
      '#title' => $this->t('Normalizer'),
      '#description' => $this->t('Select entity normalizer.'),
      '#options' => array_map(function ($definition) {
        return $definition['label'];
      }, $entity_normalizer_definitions),
      '#default_value' => $normalizer,
      '#ajax' => $ajax_attribute,
    ];

    if ($normalizer) {
      try {
        $normalizer_instance = $index->getNormalizerInstance();
        $form_state->set('bundle_normalizer', $normalizer_instance);

        // Prepare the subform.
        $subform = [];
        $subform_state = SubformState::createForSubform($subform, $form, $form_state);
        $configuration_form = $normalizer_instance->buildConfigurationForm([], $subform_state);

        $form['normalizer_configuration'] = [
          '#type' => 'details',
          '#open' => TRUE,
          '#title' => $this->t('Normalizer settings'),
          'configuration' => ['#tree' => TRUE] + $configuration_form,
        ];
      }
      catch (\Exception $e) {
        watchdog_exception('elasticsearch_helper_content', $e);
      }
    }

    return $form;
  }

  /**
   * @param $form
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *
   * @return array
   */
  public function reloadForm(&$form, FormStateInterface $form_state) {
    $form_state->setRebuild(TRUE);

    return $form;
  }

  /**
   * Returns TRUE if index name already exists.
   *
   * @param $index_name
   *
   * @return bool
   */
  public function indexNameExists($index_name) {
    try {
      $result = (bool) $this->entityTypeManager->getStorage('elasticsearch_content_index')->getQuery()
        ->condition('index_name', $index_name)
        ->execute();
    }
    catch (\Exception $e) {
      $result = FALSE;
    }

    return $result;
  }

  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);

    /** @var \Drupal\elasticsearch_helper_content\ElasticsearchContentIndexInterface $index */
    $index = $this->entity;

    // Get normalizer instance.
    $normalizer_instance = $index->getNormalizerInstance();

    // Submit normalizer form.
    $subform = &NestedArray::getValue($form, ['normalizer_configuration', 'configuration']);
    $subform_state = SubformState::createForSubform($subform, $form, $form_state);
    $normalizer_instance->submitConfigurationForm($subform, $subform_state);

    // Set normalizer configuration.
    $index->setNormalizerConfiguration($normalizer_instance->getConfiguration());
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    /** @var \Drupal\elasticsearch_helper_content\ElasticsearchContentIndexInterface $index */
    $index = $this->entity;
    $status = $index->save();

    if ($status) {
      $this->messenger()->addMessage($this->t('Saved the %label index.', [
        '%label' => $index->label(),
      ]));
    }
    else {
      $this->messenger()->addMessage($this->t('The %label index was not saved.', [
        '%label' => $index->label(),
      ]), MessengerInterface::TYPE_ERROR);
    }

    $form_state->setRedirectUrl($index->toUrl('collection'));

    // @todo Clear elasticsearch_index_plugins and views cache.
  }

}
