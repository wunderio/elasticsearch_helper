<?php

namespace Drupal\elasticsearch_helper_content\Plugin\ElasticsearchNormalizer\Field;

use Drupal\Core\Entity\EntityInterface;
use Drupal\elasticsearch_helper_content\ElasticsearchDataTypeDefinition;
use Drupal\elasticsearch_helper_content\ElasticsearchFieldNormalizerBase;

/**
 * @ElasticsearchFieldNormalizer(
 *   id = "field_entity_reference",
 *   label = @Translation("Entity reference (ID, label)"),
 *   field_types = {
 *     "entity_reference"
 *   }
 * )
 */
class FieldEntityReferenceNormalizer extends ElasticsearchFieldNormalizerBase {

  /**
   * {@inheritdoc}
   *
   * @param $object \Drupal\Core\Field\EntityReferenceFieldItemListInterface
   */
  public function normalize($object, array $context = []) {
    $attributes = [];

    foreach ($object->referencedEntities() as $entity) {
      $value = $this->getEntityValues($entity);

      // Do not pass empty strings.
      if ($value !== '') {
        $attributes[] = $value;
      }
    }

    return $attributes;
  }

  /**
   * Returns values of the entity.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *
   * @return array
   */
  protected function getEntityValues(EntityInterface $entity) {
    return [
      'id' => $entity->id(),
      'label' => $entity->label(),
      'label_keyword' => $entity->label(),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getPropertyDefinitions() {
    return [
      'id' => ElasticsearchDataTypeDefinition::create('integer'),
      'label' => ElasticsearchDataTypeDefinition::create('text'),
      'label_keyword' => ElasticsearchDataTypeDefinition::create('keyword'),
    ];
  }

}
