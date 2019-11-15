<?php

namespace Drupal\elasticsearch_helper_content\Plugin\ElasticsearchNormalizer\Field;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Field\FieldItemInterface;
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
   * {@inheritdoc}
   *
   * @return int
   */
  protected function getEntityValues(EntityInterface $entity, FieldItemInterface $field_item, array $context = []) {
    return $entity->id();
  }

  /**
   * {@inheritdoc}
   */
  public function getPropertyDefinitions() {
    return ElasticsearchDataTypeDefinition::create('integer');
  }

}
