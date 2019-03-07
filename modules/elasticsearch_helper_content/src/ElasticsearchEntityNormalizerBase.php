<?php

namespace Drupal\elasticsearch_helper_content;

use Drupal\Core\Entity\EntityTypeBundleInfoInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class ElasticsearchEntityNormalizerBase
 */
abstract class ElasticsearchEntityNormalizerBase extends ElasticsearchNormalizerBase {

  /**
   * @var \Drupal\Core\Entity\EntityTypeBundleInfoInterface
   */
  protected $entityTypeBundleInfo;

  /**
   * ElasticsearchEntityNormalizerBase constructor.
   *
   * @param array $configuration
   * @param $plugin_id
   * @param $plugin_definition
   * @param \Drupal\Core\Entity\EntityTypeBundleInfoInterface $entity_type_bundle_info
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, EntityTypeBundleInfoInterface $entity_type_bundle_info) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);

    $this->entityTypeBundleInfo = $entity_type_bundle_info;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity_type.bundle.info')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'entity_type' => NULL,
      'bundle' => NULL,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function normalize($object, array $context = []) {
    $data = parent::normalize($object, $context);

    $entity_type_id = $object->getEntityTypeId();
    $bundle = $object->bundle();

    // Get bundle information.
    $bundle_info = $this->entityTypeBundleInfo->getBundleInfo($entity_type_id);

    $data['id'] = $object->id();
    $data['uuid'] = $object->uuid();
    $data['entity_type'] = $entity_type_id;
    $data['bundle'] = $bundle;
    // @Todo How to get labels of entity type and bundle in current language?
    $data['entity_type_label'] = $object->getEntityType()->getLabel();
    $data['bundle_label'] = $bundle_info[$bundle]['label'];
    $data['url_internal'] = $object->toUrl()->getInternalPath();
    $data['url_alias'] = $object->toUrl()->toString();
    $data['label'] = $object->label();

    return $data;
  }

  /**
   * Returns core property definitions that are shared between entity and
   * entity field normalizers.
   *
   * @return \Drupal\elasticsearch_helper_content\ElasticsearchDataTypeDefinition[]|\Drupal\elasticsearch_helper_content\ElasticsearchDataTypeDefinition
   */
  public function getCorePropertyDefinitions() {
    return [
      'id' => ElasticsearchDataTypeDefinition::create('integer'),
      'uuid' => ElasticsearchDataTypeDefinition::create('keyword'),
      'entity_type' => ElasticsearchDataTypeDefinition::create('keyword'),
      'bundle' => ElasticsearchDataTypeDefinition::create('keyword'),
      'entity_type_label' => ElasticsearchDataTypeDefinition::create('keyword'),
      'bundle_label' => ElasticsearchDataTypeDefinition::create('keyword'),
      'url_internal' => ElasticsearchDataTypeDefinition::create('keyword'),
      'url_alias' => ElasticsearchDataTypeDefinition::create('keyword'),
      'label' => ElasticsearchDataTypeDefinition::create('text'),
    ];
  }

}
