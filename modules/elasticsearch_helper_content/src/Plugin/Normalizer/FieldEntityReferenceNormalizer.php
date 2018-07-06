<?php

namespace Drupal\elasticsearch_helper_content\Plugin\Normalizer;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Field\EntityReferenceFieldItemListInterface;
use Drupal\serialization\Normalizer\FieldNormalizer;
use Symfony\Component\Serializer\Exception\InvalidArgumentException;

/**
 * Normalizes entity reference field item list.
 */
class FieldEntityReferenceNormalizer extends FieldNormalizer {

  /**
   * {@inheritdoc}
   */
  protected $supportedInterfaceOrClass = EntityReferenceFieldItemListInterface::class;

  /**
   * {@inheritdoc}
   */
  protected $format = ['elasticsearch_helper'];

  /**
   * {@inheritdoc}
   *
   * @param $object \Drupal\Core\Field\EntityReferenceFieldItemListInterface
   */
  public function normalize($object, $format = NULL, array $context = []) {
    if (!($object instanceof $this->supportedInterfaceOrClass)) {
      throw new InvalidArgumentException();
    }

    $attributes = [];

    foreach ($object->referencedEntities() as $entity) {
      $attributes[] = $this->getValue($entity);
    }

    return $attributes;
  }

  /**
   * Returns value of the entity.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *
   * @return array
   */
  protected function getValue(EntityInterface $entity) {
    return [
      'id' => $entity->id(),
      'title' => $entity->label(),
    ];
  }

}
