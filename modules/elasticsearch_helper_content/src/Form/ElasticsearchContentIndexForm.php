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
use Drupal\elasticsearch_helper\Plugin\ElasticsearchIndexManager;
use Drupal\elasticsearch_helper_content\ElasticsearchEntityNormalizerManagerInterface;
use Drupal\elasticsearch_helper_content\Entity\ElasticsearchContentIndex;
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
   * @var \Drupal\elasticsearch_helper\Plugin\ElasticsearchIndexManager
   */
  protected $elasticsearchIndexManager;

  /**
   * ElasticsearchContentIndexForm constructor.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   * @param \Drupal\Core\Entity\EntityTypeBundleInfoInterface $entity_type_bundle_info
   * @param \Drupal\elasticsearch_helper_content\ElasticsearchEntityNormalizerManagerInterface $elasticsearch_entity_normalizer_manager
   * @param \Drupal\elasticsearch_helper\Plugin\ElasticsearchIndexManager $elasticsearch_index_manager
   */
  public function __construct(EntityTypeManagerInterface $entityTypeManager, EntityTypeBundleInfoInterface $entity_type_bundle_info, ElasticsearchEntityNormalizerManagerInterface $elasticsearch_entity_normalizer_manager, ElasticsearchIndexManager $elasticsearch_index_manager) {
    $this->entityTypeManager = $entityTypeManager;
    $this->entityTypeBundleInfo = $entity_type_bundle_info;
    $this->elasticsearchEntityNormalizerManager = $elasticsearch_entity_normalizer_manager;
    $this->elasticsearchIndexManager = $elasticsearch_index_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager'),
      $container->get('entity_type.bundle.info'),
      $container->get('plugin.manager.elasticsearch_entity_normalizer'),
      $container->get('plugin.manager.elasticsearch_index.processor')
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

    $target_entity_type = $index->getTargetEntityType();

    $form['label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Index label'),
      '#maxlength' => 255,
      '#default_value' => $index->label(),
      '#required' => TRUE,
      '#weight' => 5,
    ];

    $form['id'] = [
      '#type' => 'machine_name',
      '#default_value' => $index->id(),
      '#machine_name' => [
        'exists' => '\Drupal\elasticsearch_helper_content\Entity\ElasticsearchContentIndex::load',
      ],
      '#disabled' => !$index->isNew(),
      '#weight' => 10,
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
      '#weight' => 15,
    ];

    $form['multilingual'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Multilingual'),
      '#description' => t('Check if this index should support multiple languages.'),
      '#default_value' => $index->isMultilingual(),
      '#access' => $this->entityTypeTranslatable($target_entity_type),
      '#weight' => 20,
    ];

    $form['index_unpublished'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Index unpublished content'),
      '#description' => t('Check if this index should contain unpublished content.'),
      '#default_value' => $index->indexUnpublishedContent(),
      '#access' => $this->entityTypePublishAware($target_entity_type),
      '#weight' => 25,
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

    $form['entity_type'] = [
      '#type' => 'select',
      '#title' => $this->t('Entity type'),
      '#description' => $this->t('Select entity type for the index.'),
      '#options' => $entity_type_options,
      '#default_value' => $target_entity_type,
      '#required' => TRUE,
      '#ajax' => $ajax_attribute,
      '#weight' => 30,
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
      '#weight' => 35,
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
      '#weight' => 40,
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
          '#weight' => 45,
        ];
      }
      catch (\Exception $e) {
        $form['normalizer_configuration_error'] = [
          '#prefix' => '<div class="messages messages--error">',
          '#markup' => $this->t('An error occurred while rendering normalizer configuration form.'),
          '#suffix' => '</div>',
          '#weight' => 45,
        ];
        watchdog_exception('elasticsearch_helper_content', $e);
      }
    }

    return $form;
  }

  /**
   * Returns TRUE if entity type is translatable.
   *
   * @param $entity_type_id
   *
   * @return bool
   *
   * @throws
   */
  protected function entityTypeTranslatable($entity_type_id) {
    // Get entity type instance.
    $entity_type = $this->entityTypeManager->getDefinition($entity_type_id, FALSE);

    return $entity_type && $entity_type->isTranslatable();
  }

  /**
   * Returns TRUE if entity type supports published/unpublished status.
   *
   * @param $entity_type_id
   *
   * @return bool
   *
   * @throws
   */
  protected function entityTypePublishAware($entity_type_id) {
    // Get entity type instance.
    $entity_type = $this->entityTypeManager->getDefinition($entity_type_id, FALSE);

    return $entity_type && $entity_type->hasKey('published');
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
    $target_entity_type = $index->getTargetEntityType();

    // Set multilingual flag to FALSE if entity type is not translatable.
    if (!$this->entityTypeTranslatable($target_entity_type)) {
      $index->set('multilingual', FALSE);
    }

    // Set "index unpublished" value.
    if (!$this->entityTypePublishAware($target_entity_type)) {
      $index_unpublished = ElasticsearchContentIndex::INDEX_UNPUBLISHED_NA;
    }
    else {
      $index_unpublished = $form_state->getValue('index_unpublished') ? ElasticsearchContentIndex::INDEX_UNPUBLISHED : ElasticsearchContentIndex::INDEX_UNPUBLISHED_IGNORE;
    }
    $index->set('index_unpublished', $index_unpublished);

    // Get normalizer instance.
    $normalizer_instance = $index->getNormalizerInstance();

    // Submit normalizer form.
    $subform = &NestedArray::getValue($form, ['normalizer_configuration', 'configuration']);
    $subform_state = SubformState::createForSubform($subform, $form, $form_state);
    $normalizer_instance->submitConfigurationForm($subform, $subform_state);

    // Set normalizer configuration.
    $index->setNormalizerConfiguration($normalizer_instance->getConfiguration());

    // Clear cached Elasticsearch index plugin definitions.
    $this->elasticsearchIndexManager->clearCachedDefinitions();
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
