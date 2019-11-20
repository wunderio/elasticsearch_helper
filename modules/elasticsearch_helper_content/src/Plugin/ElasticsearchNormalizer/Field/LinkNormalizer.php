<?php

namespace Drupal\elasticsearch_helper_content\Plugin\ElasticsearchNormalizer\Field;

use Drupal\Core\Field\FieldItemInterface;
use Drupal\elasticsearch_helper_content\ElasticsearchDataTypeDefinition;
use Drupal\elasticsearch_helper_content\ElasticsearchFieldNormalizerBase;

/**
 * @ElasticsearchFieldNormalizer(
 *   id = "link",
 *   label = @Translation("Link (URI, title)"),
 *   field_types = {
 *     "link"
 *   },
 * )
 */
class LinkNormalizer extends ElasticsearchFieldNormalizerBase {

  /**
   * {@inheritdoc}
   */
  public function getFieldItemValue(FieldItemInterface $item, array $context = []) {
    return [
      'uri' => $item->get('uri')->getValue(),
      'title' => $item->get('title')->getValue(),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getPropertyDefinitions() {
    $definition = ElasticsearchDataTypeDefinition::create('object')
      ->addProperty('uri', ElasticsearchDataTypeDefinition::create('keyword'))
      ->addProperty('title', ElasticsearchDataTypeDefinition::create('text'));

    return $definition;
  }

}
