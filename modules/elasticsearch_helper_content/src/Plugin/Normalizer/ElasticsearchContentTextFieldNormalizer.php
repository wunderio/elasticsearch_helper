<?php

namespace Drupal\elasticsearch_helper_content\Plugin\Normalizer;

use Drupal\serialization\Normalizer\FieldNormalizer;

/**
 * Normalizes / denormalizes Fields
 */
class ElasticsearchContentTextFieldNormalizer extends FieldNormalizer {
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
    $data = parent::normalize($object, $format, $context);

    $attributes = [];

    foreach ($data as $key => $item) {
      $attributes[] = (string) $item['value'];
    }

    return $attributes;
  }

}
