<?php

namespace Drupal\elasticsearch_helper_content\Plugin\ElasticsearchNormalizer\Entity;

use Drupal\Core\Entity\EntityTypeBundleInfoInterface;
use Drupal\elasticsearch_helper_content\EntityRendererInterface;
use Drupal\elasticsearch_helper_content\ElasticsearchDataTypeDefinition;
use Drupal\elasticsearch_helper_content\ElasticsearchEntityContentNormalizerInterface;
use Drupal\elasticsearch_helper_content\ElasticsearchEntityNormalizerBase;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * @ElasticsearchEntityNormalizer(
 *   id = "content",
 *   label = @Translation("Content entity"),
 *   weight = 0
 * )
 */
class ElasticsearchEntityContentNormalizer extends ElasticsearchEntityNormalizerBase implements ElasticsearchEntityContentNormalizerInterface {

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
   * @param \Drupal\Core\Entity\EntityTypeBundleInfoInterface $entity_type_bundle_info
   * @param \Drupal\elasticsearch_helper_content\EntityRendererInterface $entity_renderer
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, EntityTypeBundleInfoInterface $entity_type_bundle_info, EntityRendererInterface $entity_renderer) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $entity_type_bundle_info);

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
      $container->get('entity_type.bundle.info'),
      $container->get('elasticsearch_helper_content.entity_renderer')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function normalize($object, array $context = []) {
    $data = parent::normalize($object, $context);

    $data['created'] = $object->hasField('created') ? $object->created->value : NULL;
    // No status field => assume 1 to simplify filtering cross entity types.
    $data['status'] = $object->hasField('status') ? boolval($object->status->value) : TRUE;
    $data['content'] = $this->entityRenderer->renderEntityPlainText($object, 'search_index');
    $data['rendered_search_result'] = $this->entityRenderer->renderEntity($object, 'search_result');

    return $data;
  }

  /**
   * {@inheritdoc}
   */
  public function getPropertyDefinitions(array $context = []) {
    // Get core property definitions.
    $core_property_definitions = $this->getCorePropertyDefinitions($context);

    return array_merge($core_property_definitions, [
      'created' => ElasticsearchDataTypeDefinition::create('date', [
        'type' => 'date',
        'format' => 'epoch_second',
      ]),
      'status' => ElasticsearchDataTypeDefinition::create('boolean'),
      'content' => ElasticsearchDataTypeDefinition::create('text', [
        // Trade off index size for better highlighting.
        'term_vector' => 'with_positions_offsets',
      ]),
      'rendered_search_result' => ElasticsearchDataTypeDefinition::create('keyword', [
        'index' => FALSE,
        'store' => TRUE,
      ]),
    ]);
  }

}
