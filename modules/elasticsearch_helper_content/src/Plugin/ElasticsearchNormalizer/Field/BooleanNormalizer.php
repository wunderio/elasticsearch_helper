<?php

namespace Drupal\elasticsearch_helper_content\Plugin\ElasticsearchNormalizer\Field;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Field\FieldItemInterface;
use Drupal\elasticsearch_helper_content\ElasticsearchDataTypeDefinition;
use Drupal\elasticsearch_helper_content\ElasticsearchFieldNormalizerBase;

/**
 * @ElasticsearchFieldNormalizer(
 *   id = "boolean",
 *   label = @Translation("Boolean"),
 *   field_types = {
 *     "boolean"
 *   }
 * )
 */
class BooleanNormalizer extends ElasticsearchFieldNormalizerBase {

  /**
   * {@inheritdoc}
   */
  public function getFieldItemValue(EntityInterface $entity, FieldItemInterface $item, array $context = []) {
    return (boolean) $item->get('value')->getValue();
  }

  /**
   * {@inheritdoc}
   */
  public function getPropertyDefinitions() {
    return ElasticsearchDataTypeDefinition::create('boolean');
  }

}
