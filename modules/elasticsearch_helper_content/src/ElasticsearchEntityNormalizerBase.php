<?php

namespace Drupal\elasticsearch_helper_content;

/**
 * Class ElasticsearchEntityNormalizerBase
 */
abstract class ElasticsearchEntityNormalizerBase extends ElasticsearchNormalizerBase implements ElasticsearchEntityNormalizerInterface {

  /**
   * {@inheritdoc}
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   */
  public function normalize($entity, array $context = []) {
    $entity_type_id = $entity->getEntityTypeId();
    $bundle = $entity->bundle();

    $data['id'] = $entity->id();
    $data['uuid'] = $entity->uuid();
    $data['entity_type'] = $entity_type_id;
    $data['bundle'] = $bundle;
    $data['langcode'] = $entity->language()->getId();

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
