<?php

namespace Drupal\elasticsearch_helper_views\Plugin\views\field;

use Drupal\Core\Cache\CacheableDependencyInterface;
use Drupal\Core\Entity\ContentEntityTypeInterface;
use Drupal\Core\Entity\EntityDisplayRepositoryInterface;
use Drupal\Core\Entity\EntityManagerInterface;
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

  /** @var \Drupal\Core\Entity\EntityManagerInterface $entityManager */
  protected $entityManager;

  /** @var \Drupal\Core\Language\LanguageManagerInterface $languageManager */
  protected $languageManager;

  /** @var EntityDisplayRepositoryInterface $entityDisplayRepository */
  protected $entityDisplayRepository;

  /**
   * RenderedEntity constructor.
   *
   * @param array $configuration
   * @param string $plugin_id
   * @param array $plugin_definition
   * @param \Drupal\Core\Entity\EntityManagerInterface $entity_manager
   * @param \Drupal\Core\Language\LanguageManagerInterface $language_manager
   * @param \Drupal\Core\Entity\EntityDisplayRepositoryInterface $entity_display_repository
   */
  public function __construct(array $configuration, $plugin_id, array $plugin_definition, EntityManagerInterface $entity_manager, LanguageManagerInterface $language_manager, EntityDisplayRepositoryInterface $entity_display_repository) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);

    $this->entityManager = $entity_manager;
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
      $container->get('entity.manager'),
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
    foreach ($this->entityManager->getDefinitions() as $entity_type) {
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
    foreach ($this->getContentEntityTypes() as $entity_type_id => $entity_type_label) {
      $options['view_mode']['default'][$entity_type_id] = 'default';
    }

    return $options;
  }

  /**
   * {@inheritdoc}
   */
  public function buildOptionsForm(&$form, FormStateInterface $form_state) {
    parent::buildOptionsForm($form, $form_state);

    // Prepare entity type listing.
    foreach ($this->getContentEntityTypes() as $entity_type_id => $entity_type_label) {
      $form['view_mode'][$entity_type_id] = [
        '#type' => 'select',
        '#options' => $this->entityDisplayRepository->getViewModeOptions($entity_type_id),
        '#title' => $this->t('View mode for @content_type', ['@content_type' => $entity_type_label]),
        '#default_value' => $this->options['view_mode'][$entity_type_id],
      ];
    }
  }

  /**
   * {@inheritdoc}
   */
  public function render(ResultRow $values) {
    $entity = $this->getEntityTranslation($this->getEntity($values), $values);
    $build = [];
    if (isset($entity)) {
      $access = $entity->access('view', NULL, TRUE);
      $build['#access'] = $access;
      if ($access->isAllowed()) {
        $mode_mode = $this->options['view_mode'][$this->getEntityTypeId()];
        $view_builder = $this->entityManager->getViewBuilder($this->getEntityTypeId());
        $build += $view_builder->view($entity, $mode_mode);
      }
    }
    return $build;
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheContexts() {
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheTags() {
    $view_display_storage = $this->entityManager->getStorage('entity_view_display');
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
      return $this->view->result[$row_index]->_entity->getEntityTypeId();
    }

    return '';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEntityManager() {
    return $this->entityManager;
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
