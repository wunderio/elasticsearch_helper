<?php

namespace Drupal\elasticsearch_helper_content\Plugin\Normalizer;

use Drupal\serialization\Normalizer\FieldNormalizer;

/**
 * Normalizes / denormalizes Fields
 */
class ElasticsearchContentReferenceFieldNormalizer extends FieldNormalizer {
  /**
   * Supported formats.
   *
   * @var array
   */
  protected $format = ['elasticsearch_helper'];

  /**
   * {@inheritdoc}
   */
  public function normalize($object, $format = NULL, array $context = []) {
    $attributes = [];

    foreach ($object->referencedEntities() as $entity) {
      $attributes[] = [
        'id' => $entity->id(),
        'title' => $entity->label(),
      ];
    }

    return $attributes;
  }

}
