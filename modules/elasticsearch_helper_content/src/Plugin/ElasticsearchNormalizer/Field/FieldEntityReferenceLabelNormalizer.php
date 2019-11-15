<?php

namespace Drupal\elasticsearch_helper_content\Plugin\ElasticsearchNormalizer\Field;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Field\FieldItemInterface;
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
   * {@inheritdoc}
   *
   * @return string
   */
  protected function getEntityValues(EntityInterface $entity, FieldItemInterface $field_item, array $context = []) {
    return $entity->label();
  }

  /**
   * {@inheritdoc}
   */
  public function getPropertyDefinitions() {
    return ElasticsearchDataTypeDefinition::create('text');
  }

}
