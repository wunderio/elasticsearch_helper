<?php

namespace Drupal\elasticsearch_helper_content\Plugin\Normalizer;

use Drupal\Core\Field\FieldItemInterface;

/**
 * Normalizes textual field item list.
 */
class FieldTextNormalizer extends FieldNormalizerBase {

  /**
   * {@inheritdoc}
   */
  protected $format = ['elasticsearch_helper_content.field_text'];

  /**
   * Returns field value.
   *
   * @param \Drupal\Core\Field\FieldItemInterface $item
   *
   * @return string
   */
  public function getValue(FieldItemInterface $item) {
    return $item->get('value')->getValue();
  }

}
