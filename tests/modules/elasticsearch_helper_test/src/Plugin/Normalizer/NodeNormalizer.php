<?php

namespace Drupal\elasticsearch_helper_test\Plugin\Normalizer;

use Drupal\node\Entity\Node;
use Drupal\serialization\Normalizer\ContentEntityNormalizer;

/**
 * Normalizes / denormalizes Drupal nodes into an array structure good for ES.
 */
class NodeNormalizer extends ContentEntityNormalizer {

  /**
   * Supported formats.
   *
   * @var array
   */
  protected $format = ['elasticsearch_helper_test'];

  /**
   * {@inheritdoc}
   */
  public function normalize($entity, $format = NULL, array $context = []): array|string|int|float|bool|\ArrayObject|NULL {
    /** @var \Drupal\node\Entity\Node $object */
    $data = [
      'id' => $object->id(),
      'uuid' => $object->uuid(),
      'title' => $object->getTitle(),
      'status' => $object->isPublished(),
    ];

    return $data;
  }

  /**
   * {@inheritdoc}
   */
  public function getSupportedTypes(?string $format): array {
    return [
      Node::class => TRUE,
    ];
  }

}
