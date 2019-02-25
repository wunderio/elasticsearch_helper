<?php

namespace Drupal\elasticsearch_helper_content\Plugin\ElasticsearchNormalizer\Field;

use Drupal\Core\Field\FieldItemInterface;
use Drupal\elasticsearch_helper_content\ElasticsearchDataTypeDefinition;
use Drupal\elasticsearch_helper_content\ElasticsearchFieldNormalizerBase;

/**
 * @ElasticsearchFieldNormalizer(
 *   id = "field_link",
 *   label = @Translation("Link"),
 *   field_types = {
 *     "link"
 *   },
 * )
 */
class ElasticsearchFieldLinkNormalizer extends ElasticsearchFieldNormalizerBase {

  /**
   * {@inheritdoc}
   */
  public function getValue(FieldItemInterface $item, array $context = []) {
    return [
      'uri' => $item->get('value')->getValue(),
      'title' => $item->get('value')->getValue(),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getPropertyDefinitions(array $context = []) {
    return [
      'uri' => ElasticsearchDataTypeDefinition::create('keyword'),
      'title' => ElasticsearchDataTypeDefinition::create('text')
    ];
  }

}
