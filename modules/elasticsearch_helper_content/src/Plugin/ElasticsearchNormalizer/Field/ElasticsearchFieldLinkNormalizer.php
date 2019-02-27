<?php

namespace Drupal\elasticsearch_helper_content\Plugin\ElasticsearchNormalizer\Field;

use Drupal\Core\Field\FieldItemInterface;
use Drupal\elasticsearch_helper_content\ElasticsearchDataTypeDefinition;
use Drupal\elasticsearch_helper_content\ElasticsearchFieldNormalizerBase;

/**
 * @ElasticsearchFieldNormalizer(
 *   id = "field_link",
 *   label = @Translation("Link (URI, title)"),
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
      'uri' => $item->get('uri')->getValue(),
      'title' => $item->get('title')->getValue(),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getPropertyDefinitions() {
    return [
      'uri' => ElasticsearchDataTypeDefinition::create('keyword'),
      'title' => ElasticsearchDataTypeDefinition::create('text')
    ];
  }

}
