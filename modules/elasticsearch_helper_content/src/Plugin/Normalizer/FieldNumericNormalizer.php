<?php

namespace Drupal\elasticsearch_helper_content\Plugin\Normalizer;

use Drupal\Core\Field\FieldItemInterface;

/**
 * Normalizes numeric field item list.
 */
class FieldNumericNormalizer extends FieldNormalizerBase {

  /**
   * {@inheritdoc}
   */
  protected $format = ['elasticsearch_helper_content.field_numeric'];

  /**
   * Returns field value.
   *
   * @param \Drupal\Core\Field\FieldItemInterface $item
   *
   * @return string
   */
  public function getValue(FieldItemInterface $item) {
    // Adding 0 to a string would produce integer or float.
    $value = $item->get('value')->getValue();
    return $value + 0;
  }

}
