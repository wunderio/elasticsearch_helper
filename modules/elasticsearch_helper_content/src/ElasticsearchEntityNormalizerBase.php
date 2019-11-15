<?php

namespace Drupal\elasticsearch_helper_content;

/**
 * Class ElasticsearchEntityNormalizerBase
 */
abstract class ElasticsearchEntityNormalizerBase extends ElasticsearchNormalizerBase {

  /**
   * @var string
   */
  protected $targetEntityType;

  /**
   * @var string
   */
  protected $targetBundle;

  /**
   * ElasticsearchEntityNormalizerBase constructor.
   *
   * @param array $configuration
   * @param $plugin_id
   * @param $plugin_definition
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition) {
    if (!isset($configuration['entity_type'], $configuration['bundle'])) {
      throw new \InvalidArgumentException(t('Entity type or bundle key is not provided in plugin configuration.'));
    }

    $this->targetEntityType = $configuration['entity_type'];
    $this->targetBundle = $configuration['bundle'];
    unset($configuration['entity_type'], $configuration['bundle']);

    parent::__construct($configuration, $plugin_id, $plugin_definition);
  }

  /**
   * {@inheritdoc}
   *
   * @param \Drupal\Core\Entity\EntityInterface $object
   */
  public function normalize($object, array $context = []) {
    $data = parent::normalize($object, $context);

    $entity_type_id = $object->getEntityTypeId();
    $bundle = $object->bundle();

    $data['id'] = $object->id();
    $data['uuid'] = $object->uuid();
    $data['entity_type'] = $entity_type_id;
    $data['bundle'] = $bundle;
    $data['langcode'] = $object->language()->getId();

    return $data;
  }

  /**
   * Returns core property definitions that are shared between entity and
   * entity field normalizers.
   *
   * @return \Drupal\elasticsearch_helper_content\ElasticsearchDataTypeDefinition[]
   */
  public function getCorePropertyDefinitions() {
    return [
      'id' => ElasticsearchDataTypeDefinition::create('integer'),
      'uuid' => ElasticsearchDataTypeDefinition::create('keyword'),
      'entity_type' => ElasticsearchDataTypeDefinition::create('keyword'),
      'bundle' => ElasticsearchDataTypeDefinition::create('keyword'),
      'langcode' => ElasticsearchDataTypeDefinition::create('keyword'),
    ];
  }

}
