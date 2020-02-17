<?php

namespace Drupal\elasticsearch_helper_content;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Field\FieldItemInterface;
use Drupal\Core\Field\FieldItemListInterface;

/**
 * Class FieldNormalizerBase
 */
abstract class ElasticsearchFieldNormalizerBase extends ElasticsearchNormalizerBase implements ElasticsearchFieldNormalizerInterface {

  /**
   * {@inheritdoc}
   */
  public function normalize($entity, $field, array $context = []) {
    $result = [];

    try {
      if ($field) {
        $cardinality = $this->getCardinality($field);

        foreach ($field as $field_item) {
          $value = $this->getFieldItemValue($entity, $field_item, $context);

          if ($cardinality === 1) {
            return $value;
          }

          // Do not pass empty strings.
          if ($value !== '') {
            $result[] = $value;
          }
        }
      }
    }
    catch (\Exception $e) {
      watchdog_exception('elasticsearch_helper_content', $e);
    }

    return $result;
  }

  /**
   * Returns field cardinality.
   *
   * Defaults to 1 if cardinality cannot be established from field definition.
   *
   * @param \Drupal\Core\Field\FieldItemListInterface $field
   *
   * @return int
   */
  protected function getCardinality($field) {
    if ($field instanceof FieldItemListInterface) {
      return $field->getFieldDefinition()->getFieldStorageDefinition()->getCardinality();
    }

    return 1;
  }

  /**
   * Returns value of the field item.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   * @param \Drupal\Core\Field\FieldItemInterface $item
   * @param array $context Context options for the normalizer
   *
   * @return mixed
   */
  public function getFieldItemValue(EntityInterface $entity, FieldItemInterface $item, array $context = []) {
    return '';
  }

}
