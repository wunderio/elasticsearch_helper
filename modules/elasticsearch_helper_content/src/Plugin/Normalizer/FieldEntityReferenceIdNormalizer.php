<?php

namespace Drupal\elasticsearch_helper_content\Plugin\Normalizer;

use Drupal\Core\Entity\EntityInterface;

/**
 * Normalizes entity reference field item list (only ID).
 */
class FieldEntityReferenceIdNormalizer extends FieldEntityReferenceNormalizer {

  /**
   * {@inheritdoc}
   */
  protected $format = ['elasticsearch_helper_content.field_entity_reference_id'];

  /**
   * {@inheritdoc}
   *
   * Returns entity ID.
   */
  protected function getValue(EntityInterface $entity) {
    return $entity->id();
  }

}
