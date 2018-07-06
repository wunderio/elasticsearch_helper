<?php

namespace Drupal\elasticsearch_helper_content\Plugin\Normalizer;

use Drupal\serialization\Normalizer\FieldNormalizer;
use Drupal\Core\Field\EntityReferenceFieldItemList;

/**
 * Normalizes / denormalizes Fields
 */
class ElasticsearchContentReferenceIdFieldNormalizer extends FieldNormalizer {
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

    if ($object instanceof EntityReferenceFieldItemList) {
      foreach ($object->referencedEntities() as $entity) {
        $attributes[] = [
          $entity->id(),
        ];
      }
    }

    return $attributes;
  }

}
