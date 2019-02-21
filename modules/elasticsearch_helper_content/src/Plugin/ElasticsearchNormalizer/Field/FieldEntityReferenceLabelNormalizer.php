<?php

namespace Drupal\elasticsearch_helper_content\Plugin\ElasticsearchNormalizer\Field;

use Drupal\Core\Entity\EntityInterface;
use Drupal\elasticsearch_helper_content\ElasticsearchDataTypeDefinition;

/**
 * @ElasticsearchFieldNormalizer(
 *   id = "field_entity_reference_label",
 *   label = @Translation("Entity reference (label)"),
 *   field_types = {
 *     "entity_reference"
 *   }
 * )
 */
class FieldEntityReferenceLabelNormalizer extends FieldEntityReferenceNormalizer {

  /**
   * Returns values of the entity.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *
   * @return string
   */
  protected function getEntityValues(EntityInterface $entity) {
    return $entity->label();
  }

  /**
   * {@inheritdoc}
   */
  public function getPropertyDefinitions() {
    return ElasticsearchDataTypeDefinition::create('text');
  }

}
