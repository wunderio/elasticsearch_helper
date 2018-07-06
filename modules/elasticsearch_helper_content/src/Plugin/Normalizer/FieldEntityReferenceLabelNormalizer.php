<?php

namespace Drupal\elasticsearch_helper_content\Plugin\Normalizer;

use Drupal\Core\Entity\EntityInterface;

/**
 * Normalizes entity reference field item list (only label).
 */
class FieldEntityReferenceLabelNormalizer extends FieldEntityReferenceNormalizer {

  /**
   * {@inheritdoc}
   *
   * Returns entity label.
   */
  protected function getValue(EntityInterface $entity) {
    return $entity->label();
  }

}
