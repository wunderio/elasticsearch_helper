<?php

namespace Drupal\elasticsearch_helper_content;

use Drupal\Core\Field\FieldItemInterface;

/**
 * Class FieldNormalizerBase
 */
abstract class ElasticsearchFieldNormalizerBase extends ElasticsearchNormalizerBase {

  /**
   * {@inheritdoc}
   *
   * @param $object \Drupal\Core\Field\FieldItemListInterface
   */
  public function normalize($object, array $context = []) {
    $attributes = [];

    foreach ($object as $item) {
      $value = $this->getValue($item, $context);

      // Do not pass empty strings.
      if ($value !== '') {
        $attributes[] = $value;
      }

    }

    return $attributes;
  }

  /**
   * Returns value of the field item.
   *
   * @param $item \Drupal\Core\Field\FieldItemInterface
   * @param array $context Context options for the normalizer
   *
   * @return mixed
   */
  public function getValue(FieldItemInterface $item, array $context = []) {
    return '';
  }

}
