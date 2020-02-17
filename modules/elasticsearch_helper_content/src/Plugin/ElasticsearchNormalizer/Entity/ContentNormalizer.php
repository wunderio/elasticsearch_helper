<?php

namespace Drupal\elasticsearch_helper_content\Plugin\ElasticsearchNormalizer\Entity;

use Drupal\Core\Entity\EntityDisplayRepositoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\elasticsearch_helper_content\ElasticsearchNormalizerHelper;
use Drupal\elasticsearch_helper_content\EntityRendererInterface;
use Drupal\elasticsearch_helper_content\ElasticsearchDataTypeDefinition;
use Drupal\elasticsearch_helper_content\ElasticsearchEntityNormalizerBase;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * @ElasticsearchEntityNormalizer(
 *   id = "content",
 *   label = @Translation("Content entity"),
 *   weight = 5
 * )
 */
class ContentNormalizer extends ElasticsearchEntityNormalizerBase {

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
   * @var \Drupal\elasticsearch_helper_content\ElasticsearchNormalizerHelper
   */
  protected $normalizerHelper;

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
  public function __construct(array $configuration, $plugin_id, $plugin_definition, EntityTypeManagerInterface $entity_type_manager, EntityDisplayRepositoryInterface $entity_display_repository, EntityRendererInterface $entity_renderer, ElasticsearchNormalizerHelper $normalizer_helper) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);

    $this->entityTypeManager = $entity_type_manager;
    $this->entityDisplayRepository = $entity_display_repository;
    $this->entityRenderer = $entity_renderer;
    $this->normalizerHelper = $normalizer_helper;
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
      $container->get('elasticsearch_helper_content.entity_renderer'),
      $container->get('elasticsearch_helper_content.normalizer_helper')
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
   *
   * @param \Drupal\Core\Entity\ContentEntityInterface $entity
   */
  public function normalize($entity, array $context = []) {
    $data = parent::normalize($entity, $context);

    $data['label'] = $entity->label();
    $data['created'] = $entity->hasField('created') ? $entity->created->value : NULL;
    // No status field => assume 1 to simplify filtering cross entity types.
    $data['status'] = $entity->hasField('status') ? boolval($entity->status->value) : TRUE;
    $data['content'] = $this->entityRenderer->renderEntityPlainText($entity, $this->configuration['view_mode']['content']);
    $data['rendered_content'] = $this->entityRenderer->renderEntity($entity, $this->configuration['view_mode']['rendered_content']);

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
    $entity_view_displays = $this->normalizerHelper->getEntityViewDisplayOptions($this->configuration['entity_type'], $this->configuration['bundle']);

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
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    $this->configuration['view_mode'] = $form_state->getValue('view_mode');
  }

}
