<?php

namespace Drupal\elasticsearch_helper_content\Plugin\Normalizer;

use Drupal\serialization\Normalizer\FieldNormalizer;
use Drupal\Core\Field\Plugin\Field\FieldType\EntityReferenceItem;

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

    foreach ($object as $field_item) {
      if ($field_item instanceof EntityReferenceItem) {
        $entity = $field_item
          ->get('entity')
          ->getTarget()
          ->getValue();

        $attributes[] = [
          'id' => $entity->id(),
          'title' => $entity->label(),
        ];
      }
    }

    return $attributes;
  }

}
