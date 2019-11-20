<?php

namespace Drupal\elasticsearch_helper_content\Plugin\ElasticsearchNormalizer\Field;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\RendererInterface;
use Drupal\elasticsearch_helper_content\ElasticsearchDataTypeDefinition;
use Drupal\elasticsearch_helper_content\ElasticsearchFieldNormalizerBase;
use Drupal\elasticsearch_helper_content\ElasticsearchNormalizerHelper;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * @ElasticsearchFieldNormalizer(
 *   id = "rendered_content",
 *   label = @Translation("Rendered content"),
 *   field_types = {
 *     "all"
 *   },
 *   weight = 5
 * )
 */
class RenderedContentNormalizer extends ElasticsearchFieldNormalizerBase {

  /**
   * @var \Drupal\Core\Entity\EntityViewBuilderInterface
   */
  protected $viewBuilder;

  /**
   * @var \Drupal\elasticsearch_helper_content\ElasticsearchNormalizerHelper
   */
  protected $normalizerHelper;

  /**
   * @var \Drupal\Core\Render\Renderer
   */
  protected $renderer;

  /**
   * ElasticsearchFieldRenderedContentNormalizer constructor.
   *
   * @param array $configuration
   * @param $plugin_id
   * @param $plugin_definition
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   * @param \Drupal\elasticsearch_helper_content\ElasticsearchNormalizerHelper $normalizer_helper
   * @param \Drupal\Core\Render\RendererInterface $renderer
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, EntityTypeManagerInterface $entity_type_manager, ElasticsearchNormalizerHelper $normalizer_helper, RendererInterface $renderer) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);

    $this->viewBuilder = $entity_type_manager->getViewBuilder($this->targetEntityType);
    $this->normalizerHelper = $normalizer_helper;
    $this->renderer = $renderer;
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
      $container->get('elasticsearch_helper_content.normalizer_helper'),
      $container->get('renderer')
    );
  }

  /**
   * Returns the rendered content of the entity.
   *
   * @param \Drupal\Core\Entity\EntityInterface $object
   * @param array $context
   *
   * @return \Drupal\Component\Render\MarkupInterface|mixed
   */
  public function normalizeEntity($object, array $context = []) {
    $build = $this->viewBuilder->view($object, $this->configuration['view_mode']);

    return $this->renderer->renderRoot($build);
  }

  /**
   * {@inheritdoc}
   *
   * @param \Drupal\Core\Field\FieldItemInterface $object
   */
  public function normalize($object, array $context = []) {
    $result = [];

    if ($object) {
      $build = $object->view($this->configuration['view_mode']);
      $result = $this->renderer->renderRoot($build);
    }

    return $result;
  }

  /**
   * {@inheritdoc}
   */
  public function getPropertyDefinitions() {
    return ElasticsearchDataTypeDefinition::create('keyword');
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'view_mode' => 'default',
    ] + parent::defaultConfiguration();
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $entity_view_displays = $this->normalizerHelper->getEntityViewDisplayOptions($this->targetEntityType, $this->targetBundle);

    return [
      'view_mode' => [
        '#type' => 'select',
        '#title' => t('View mode'),
        '#options' => $entity_view_displays,
        '#default_value' => $this->configuration['view_mode'],
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
