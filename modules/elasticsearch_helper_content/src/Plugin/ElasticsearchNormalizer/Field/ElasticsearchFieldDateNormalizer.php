<?php

namespace Drupal\elasticsearch_helper_content\Plugin\ElasticsearchNormalizer\Field;

use Drupal\Core\Field\FieldItemInterface;
use Drupal\elasticsearch_helper_content\ElasticsearchDataTypeDefinition;
use Drupal\elasticsearch_helper_content\ElasticsearchFieldNormalizerBase;

/**
 * @ElasticsearchFieldNormalizer(
 *   id = "field_date",
 *   label = @Translation("Date"),
 *   field_types = {
 *     "float",
 *     "decimal"
 *   }
 * )
 */
class ElasticsearchFieldDateNormalizer extends ElasticsearchFieldNormalizerBase {

  /**
   * {@inheritdoc}
   */
  public function getValue(FieldItemInterface $item, array $context = []) {
    // @todo Allow date format to be overridden with $context['format'].
    $date_value = $item->get('value')->getValue();

    return [
      'value' => $date_value,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getPropertyDefinitions() {
    return ElasticsearchDataTypeDefinition::create('date');
  }

}
