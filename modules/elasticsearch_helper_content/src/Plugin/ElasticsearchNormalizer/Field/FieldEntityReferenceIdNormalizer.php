<?php

namespace Drupal\elasticsearch_helper_content\Plugin\ElasticsearchNormalizer\Field;

use Drupal\Core\Entity\EntityInterface;
use Drupal\elasticsearch_helper_content\ElasticsearchDataTypeDefinition;

/**
 * @ElasticsearchFieldNormalizer(
 *   id = "field_entity_reference_id",
 *   label = @Translation("Entity reference (ID)"),
 *   field_types = {
 *     "entity_reference"
 *   }
 * )
 */
class FieldEntityReferenceIdNormalizer extends FieldEntityReferenceNormalizer {

  /**
   * Returns values of the entity.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *
   * @return int
   */
  protected function getEntityValues(EntityInterface $entity) {
    return $entity->id();
  }

  /**
   * {@inheritdoc}
   */
  public function getPropertyDefinitions(array $context = []) {
    return ElasticsearchDataTypeDefinition::create('integer');
  }

}
