<?php

namespace Drupal\elasticsearch_helper_content\Plugin\ElasticsearchNormalizer\Entity;

use Drupal\Core\Entity\EntityDisplayRepositoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\elasticsearch_helper_content\EntityRendererInterface;
use Drupal\elasticsearch_helper_content\ElasticsearchDataTypeDefinition;
use Drupal\elasticsearch_helper_content\ElasticsearchEntityNormalizerBase;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * @ElasticsearchEntityNormalizer(
 *   id = "content",
 *   label = @Translation("Content entity"),
 *   weight = 0
 * )
 */
class ElasticsearchEntityContentNormalizer extends ElasticsearchEntityNormalizerBase {

  /**
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * @var \Drupal\Core\Entity\EntityDisplayRepositoryInterface
   */
  protected $entityDisplayRepository;

  /**
   * @var \Drupal\elasticsearch_helper_content\EntityRendererInterface
   */
  protected $entityRenderer;

  /**
   * ElasticsearchEntityContentNormalizer constructor.
   *
   * @param array $configuration
   * @param $plugin_id
   * @param $plugin_definition
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   * @param \Drupal\Core\Entity\EntityDisplayRepositoryInterface $entity_display_repository
   * @param \Drupal\elasticsearch_helper_content\EntityRendererInterface $entity_renderer
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, EntityTypeManagerInterface $entity_type_manager, EntityDisplayRepositoryInterface $entity_display_repository, EntityRendererInterface $entity_renderer) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);

    $this->entityTypeManager = $entity_type_manager;
    $this->entityDisplayRepository = $entity_display_repository;
    $this->entityRenderer = $entity_renderer;
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
      $container->get('entity_display.repository'),
      $container->get('elasticsearch_helper_content.entity_renderer')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'view_mode' => [
        'content' => '',
        'rendered_content' => '',
      ],
    ] + parent::defaultConfiguration();
  }

  /**
   * {@inheritdoc}
   */
  public function normalize($object, array $context = []) {
    $data = parent::normalize($object, $context);

    $data['label'] = $object->label();
    $data['created'] = $object->hasField('created') ? $object->created->value : NULL;
    // No status field => assume 1 to simplify filtering cross entity types.
    $data['status'] = $object->hasField('status') ? boolval($object->status->value) : TRUE;
    $data['content'] = $this->entityRenderer->renderEntityPlainText($object, $this->configuration['view_mode']['content']);
    $data['rendered_content'] = $this->entityRenderer->renderEntity($object, $this->configuration['view_mode']['rendered_content']);

    return $data;
  }

  /**
   * {@inheritdoc}
   */
  public function getPropertyDefinitions() {
    // Get core property definitions.
    $core_property_definitions = $this->getCorePropertyDefinitions();

    return array_merge($core_property_definitions, [
      'label' => ElasticsearchDataTypeDefinition::create('text'),
      'created' => ElasticsearchDataTypeDefinition::create('date', [
        'type' => 'date',
        'format' => 'epoch_second',
      ]),
      'status' => ElasticsearchDataTypeDefinition::create('boolean'),
      'content' => ElasticsearchDataTypeDefinition::create('text', [
        // Trade off index size for better highlighting.
        'term_vector' => 'with_positions_offsets',
      ]),
      'rendered_content' => ElasticsearchDataTypeDefinition::create('keyword', [
        'index' => FALSE,
        'store' => TRUE,
      ]),
    ]);
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $entity_view_displays = $this->getEntityViewDisplayOptions();

    return [
      '#tree' => TRUE,
      'view_mode' => [
        'content' => [
          '#type' => 'select',
          '#title' => t('Content view mode'),
          '#options' => $entity_view_displays,
          '#default_value' => $this->configuration['view_mode']['content'],
        ],
        'rendered_content' => [
          '#type' => 'select',
          '#title' => t('Rendered content view mode'),
          '#options' => $entity_view_displays,
          '#default_value' => $this->configuration['view_mode']['rendered_content'],
        ],
      ],
    ];
  }

  /**
   * Returns a list of enabled view modes.
   *
   * @return array
   */
  protected function getEntityViewDisplayOptions() {
    $view_modes = [];

    try {
      // Get all view modes.
      $view_modes = $this->entityDisplayRepository->getViewModes($this->configuration['entity_type']);

      // Get entity view display IDs (enabled view modes).
      $entity_view_display_storage = $this->entityTypeManager->getStorage('entity_view_display');
      $query = $entity_view_display_storage->getQuery()
        ->condition('targetEntityType', $this->configuration['entity_type'])
        ->condition('bundle', $this->configuration['bundle'])
        ->condition('status', TRUE);

      // Get the view modes from retrieved entity view displays.
      $enabled_view_mode_ids = [];
      if ($entity_view_display_result = $query->execute()) {
        $enabled_view_mode_ids = array_map(function ($entity) {
          /** @var \Drupal\Core\Entity\Entity\EntityViewDisplay $entity */
          return $entity->getMode();
        }, $entity_view_display_storage->loadMultiple($entity_view_display_result));
      }

      // Filter out view mode that are not enabled.
      $view_modes = array_intersect_key($view_modes, array_flip($enabled_view_mode_ids));

      // Get view mode labels.
      $view_modes = array_map(function ($view_mode) {
        return $view_mode['label'];
      }, $view_modes);
    }
    catch (\Exception $e) {
      watchdog_exception('elasticsearch_helper_content', $e);
    }

    return ['default' => t('Default')] + $view_modes;
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    $this->configuration['view_mode'] = $form_state->getValue('view_mode');
  }

}
