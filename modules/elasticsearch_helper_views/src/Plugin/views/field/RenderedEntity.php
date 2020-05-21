<?php

namespace Drupal\elasticsearch_helper_views\Plugin\views\field;

use Drupal\Component\Serialization\Yaml;
use Drupal\Core\Cache\CacheableDependencyInterface;
use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\DependencyInjection\DeprecatedServicePropertyTrait;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\ContentEntityTypeInterface;
use Drupal\Core\Entity\EntityDisplayRepositoryInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\views\Entity\Render\EntityTranslationRenderTrait;
use Drupal\views\Plugin\views\field\FieldPluginBase;
use Drupal\views\ResultRow;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Cache\Cache;

/**
 * Provides a field handler which renders an entity in a certain view mode.
 *
 * @ingroup views_field_handlers
 *
 * @ViewsField("elasticsearch_rendered_entity")
 */
class RenderedEntity extends FieldPluginBase implements CacheableDependencyInterface {

  use EntityTranslationRenderTrait;
  use DeprecatedServicePropertyTrait;

  /** @var \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager */
  protected $entityTypeManager;

  /** @var \Drupal\Core\Language\LanguageManagerInterface $languageManager */
  protected $languageManager;

  /** @var EntityDisplayRepositoryInterface $entityDisplayRepository */
  protected $entityDisplayRepository;

  /** @var string $entityResultProperty */
  protected $entityResultProperty = 'search_result';

  /**
   * RenderedEntity constructor.
   *
   * @param array $configuration
   * @param string $plugin_id
   * @param array $plugin_definition
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   * @param \Drupal\Core\Language\LanguageManagerInterface $language_manager
   * @param \Drupal\Core\Entity\EntityDisplayRepositoryInterface $entity_display_repository
   */
  public function __construct(array $configuration, $plugin_id, array $plugin_definition, EntityTypeManagerInterface $entity_type_manager, LanguageManagerInterface $language_manager, EntityDisplayRepositoryInterface $entity_display_repository) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);

    $this->entityTypeManager = $entity_type_manager;
    $this->languageManager = $language_manager;
    $this->entityDisplayRepository = $entity_display_repository;
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
      $container->get('language_manager'),
      $container->get('entity_display.repository')
    );
  }

  /**
   * Returns a list of content entity types.
   *
   * @return array
   */
  protected function getContentEntityTypes() {
    $entity_types = [];
    foreach ($this->entityTypeManager->getDefinitions() as $entity_type) {
      // Filter out content entity types.
      if ($entity_type instanceof ContentEntityTypeInterface) {
        $entity_types[$entity_type->id()] = $entity_type->getLabel();
      }
    }

    return $entity_types;
  }

  /**
   * {@inheritdoc}
   */
  public function usesGroupBy() {
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function defineOptions() {
    $options = parent::defineOptions();

    $options['view_mode'] = ['default' => []];
    $options['set_result_on_entity'] = ['default' => FALSE];

    return $options;
  }

  /**
   * {@inheritdoc}
   */
  public function buildOptionsForm(&$form, FormStateInterface $form_state) {
    parent::buildOptionsForm($form, $form_state);

    $form['view_mode'] = [
      '#type' => 'textarea',
      '#title' => $this->t('View mode'),
      '#default_value' => Yaml::encode($this->options['view_mode']),
      '#description' => $this->t('Provide view mode settings for each entity type and bundle in YAML format. Example: @example', ['@example' => join('<br />', ['- node:article: default'])]),
    ];

    $form['set_result_on_entity'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Set result on the entity'),
      '#description' => $this->t('Enable to store the search result on the entity (as %property property)', ['%property' => $this->entityResultProperty]),
      '#default_value' => !empty($this->options['set_result_on_entity']),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function validateOptionsForm(&$form, FormStateInterface $form_state) {
    parent::validateOptionsForm($form, $form_state);

    $value = &$form_state->getValue(['options', 'view_mode']);

    if (empty($value)) {
      $value = '';
    }

    try {
      $validated_value = Yaml::decode($value);

      if (!is_array($validated_value)) {
        throw new \Exception('View modes must be a list with entity type/bundle pair as key (e.g., "node:page") and view mode as value (e.g, "default").');
      }
    } catch (\Exception $e) {
      $message = $e->getMessage();
      $form_state->setErrorByName('options][view_mode', !empty($message) ? $message : $this->t('Please enter valid JSON.'));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitOptionsForm(&$form, FormStateInterface $form_state) {
    parent::submitOptionsForm($form, $form_state);

    $value = Yaml::decode($form_state->getValue(['options', 'view_mode']));
    $form_state->setValue(['options', 'view_mode'], $value);
  }

  /**
   * {@inheritdoc}
   */
  public function render(ResultRow $values) {
    $build = [];
    $entity = $this->getEntity($values);

    // Elasticsearch results might not correspond to a Drupal entity.
    if ($entity instanceof ContentEntityInterface) {
      $entity = $this->getEntityTranslation($entity, $values);

      if (isset($entity)) {
        $entity_type = $entity->getEntityTypeId();
        $entity_bundle = $entity->bundle();

        // Get view mode for the entity.
        $view_mode = $this->getViewMode($entity_type, $entity_bundle);

        // Assign search results to the entity for reference.
        if ($this->options['set_result_on_entity']) {
          $entity->{$this->entityResultProperty} = $values;
        }

        // Build entity view.
        $view_builder = $this->entityTypeManager->getViewBuilder($entity_type);
        $build += $view_builder->view($entity, $view_mode);

        // Add cache contexts to the build.
        CacheableMetadata::createFromRenderArray($build)->addCacheContexts($this->getCacheContexts())->applyTo($build);

        // Check access and provide the context of the build.
        $build['#access'] = $this->getAccess($entity, $build);
      }
    }

    return $build;
  }

  /**
   * Returns view mode for given entity type and bundle.
   *
   * @param $entity_type
   * @param $bundle
   *
   * @return string
   */
  protected function getViewMode($entity_type, $bundle) {
    foreach ($this->options['view_mode'] as $settings) {
      if (isset($settings[sprintf('%s:%s', $entity_type, $bundle)])) {
        return $settings[sprintf('%s:%s', $entity_type, $bundle)];
      }

      if (isset($settings[$entity_type])) {
        return $settings[$entity_type];
      }
    }

    return 'default';
  }

  /**
   * Returns access result object for given entity.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   * @param array $build
   *
   * @return bool|\Drupal\Core\Access\AccessResultInterface
   */
  protected function getAccess(EntityInterface $entity, array $build) {
    return $entity->access('view', NULL, TRUE);
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheContexts() {
    $contexts = [];

    // If results are set on the entity, apply query plugin cache contexts.
    if ($this->options['set_result_on_entity']) {
      $contexts = Cache::mergeContexts($contexts, $this->view->getQuery()->getCacheContexts());
    }

    return $contexts;
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheTags() {
    $view_display_storage = $this->entityTypeManager->getStorage('entity_view_display');
    $view_displays = $view_display_storage->loadMultiple($view_display_storage
      ->getQuery()
      ->condition('targetEntityType', $this->getEntityTypeId())
      ->execute());

    $tags = [];
    foreach ($view_displays as $view_display) {
      $tags = array_merge($tags, $view_display->getCacheTags());
    }
    return $tags;
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheMaxAge() {
    return Cache::PERMANENT;
  }

  /**
   * {@inheritdoc}
   */
  public function query() {
  }

  /**
   * {@inheritdoc}
   */
  public function getEntityTypeId() {
    if (($row_index = $this->view->row_index) !== NULL) {
      if (!empty($this->options['relationship']) && $this->options['relationship'] != 'none') {
        if ($entity = $this->view->result[$row_index]->_relationship_entities[$this->options['relationship']]) {
          return $entity->getEntityTypeId();
        }
      }
      elseif ($entity = $this->view->result[$row_index]->_entity) {
        return $entity->getEntityTypeId();
      }
    }

    return '';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEntityManager() {
    // This relies on DeprecatedServicePropertyTrait to trigger a deprecation
    // message in case it is accessed.
    return $this->entityManager;
  }

  /**
   * {@inheritdoc}
   */
  protected function getEntityTypeManager() {
    return $this->entityTypeManager;
  }

  /**
   * {@inheritdoc}
   */
  protected function getLanguageManager() {
    return $this->languageManager;
  }

  /**
   * {@inheritdoc}
   */
  protected function getView() {
    return $this->view;
  }

}
