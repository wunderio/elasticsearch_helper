<?php

namespace Drupal\elasticsearch_helper_content\Plugin\ElasticsearchNormalizer\Field;

use Drupal\Core\Field\FieldItemInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\elasticsearch_helper_content\ElasticsearchDataTypeDefinition;
use Drupal\elasticsearch_helper_content\ElasticsearchFieldNormalizerBase;

/**
 * @ElasticsearchFieldNormalizer(
 *   id = "field_text",
 *   label = @Translation("Text"),
 *   field_types = {
 *     "string",
 *     "uuid",
 *     "language",
 *     "text",
 *     "text_long",
 *     "text_with_summary",
 *     "list_string"
 *   },
 * )
 */
class ElasticsearchFieldTextNormalizer extends ElasticsearchFieldNormalizerBase {

  /**
   * {@inheritdoc}
   */
  public function getFieldItemValue(FieldItemInterface $item, array $context = []) {
    return $item->get('value')->getValue();
  }

  /**
   * {@inheritdoc}
   */
  public function getPropertyDefinitions() {
    return ElasticsearchDataTypeDefinition::create('text');
  }

}
