<?php

namespace Drupal\elasticsearch_helper_content\Plugin\ElasticsearchNormalizer\Entity;

use Drupal\elasticsearch_helper_content\ElasticsearchDataTypeDefinition;
use Drupal\elasticsearch_helper_content\ElasticsearchEntityContentNormalizerInterface;
use Drupal\elasticsearch_helper_content\ElasticsearchEntityNormalizerBase;

/**
 * @ElasticsearchEntityNormalizer(
 *   id = "content",
 *   label = @Translation("Content entity"),
 *   weight = 0
 * )
 */
class ElasticsearchEntityContentNormalizer extends ElasticsearchEntityNormalizerBase implements ElasticsearchEntityContentNormalizerInterface {

  public function normalize($object, array $context = []) {
    return parent::normalize($object, $context);
  }

  /**
   * {@inheritdoc}
   */
  public function getPropertyDefinitions(array $context = []) {
    // Get core property definitions.
    $core_property_definitions = $this->getCorePropertyDefinitions($context);

    return array_merge($core_property_definitions, [
      'created' => ElasticsearchDataTypeDefinition::create('date', [
        'type' => 'date',
        'format' => 'epoch_second',
      ]),
      'status' => ElasticsearchDataTypeDefinition::create('boolean'),
      'content' => ElasticsearchDataTypeDefinition::create('text', [
        // Trade off index size for better highlighting.
        'term_vector' => 'with_positions_offsets',
      ]),
      'rendered_search_result' => ElasticsearchDataTypeDefinition::create('keyword', [
        'index' => FALSE,
        'store' => TRUE,
      ]),
    ]);
  }

}
