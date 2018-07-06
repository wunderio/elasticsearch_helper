<?php

namespace Drupal\elasticsearch_helper_content\Plugin\Normalizer;

use Drupal\Core\Entity\EntityInterface;

/**
 * Normalizes entity reference field item list (only label).
 */
class FieldEntityReferenceLabelNormalizer extends FieldEntityReferenceNormalizer {

  /**
   * {@inheritdoc}
   */
  protected $format = ['elasticsearch_helper_content.field_entity_reference_label'];

  /**
   * {@inheritdoc}
   *
   * Returns entity label.
   */
  protected function getValue(EntityInterface $entity) {
    return $entity->label();
  }

}
