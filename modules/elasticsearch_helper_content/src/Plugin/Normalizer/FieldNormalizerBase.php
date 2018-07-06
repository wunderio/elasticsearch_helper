<?php

namespace Drupal\elasticsearch_helper_content\Plugin\Normalizer;

use Drupal\Core\Field\FieldItemInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\serialization\Normalizer\FieldNormalizer;
use Symfony\Component\Serializer\Exception\InvalidArgumentException;

/**
 * Normalizes item list.
 */
class FieldNormalizerBase extends FieldNormalizer {

  /**
   * {@inheritdoc}
   */
  protected $supportedInterfaceOrClass = FieldItemListInterface::class;

  /**
   * {@inheritdoc}
   */
  protected $format = ['elasticsearch_helper'];

  /**
   * {@inheritdoc}
   *
   * @param $object FieldItemListInterface
   */
  public function normalize($object, $format = NULL, array $context = []) {
    $attributes = [];

    foreach ($object as $item) {
      $attributes[] = $this->getValue($item);
    }

    return $attributes;
  }

  /**
   * Validates if object can be normalized.
   *
   * - Checks if object implements a supported interface or class.
   * - Checks if "value" column exists in field storage.
   *
   * @param $object \Drupal\Core\Field\FieldItemListInterface
   */
  public function validate($object) {
    if (!($object instanceof $this->supportedInterfaceOrClass)) {
      throw new InvalidArgumentException();
    }

    if (!isset($object->getFieldDefinition()->getFieldStorageDefinition()->getColumns()['value'])) {
      throw new InvalidArgumentException();
    }
  }

  /**
   * Returns value of the field item.
   *
   * @param $item \Drupal\Core\Field\FieldItemInterface
   *
   * @return mixed
   */
  public function getValue(FieldItemInterface $item) {
    return;
  }

}
