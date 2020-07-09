<?php

namespace Drupal\elasticsearch_helper_test\Plugin\ElasticsearchIndex;

use Drupal\elasticsearch_helper\Elasticsearch\Index\FieldDefinition;
use Drupal\elasticsearch_helper\Elasticsearch\Index\MappingDefinition;
use Drupal\elasticsearch_helper\Plugin\ElasticsearchIndexBase;

/**
 * @ElasticsearchIndex(
 *   id = "node_index",
 *   label = @Translation("Node Index"),
 *   indexName = "node_index",
 *   typeName = "node",
 *   entityType = "node"
 * )
 */
class NodeIndex extends ElasticsearchIndexBase {

  /**
   * {@inheritdoc}
   */
  public function getMappingDefinition(array $context = []) {
    $keyword_field = FieldDefinition::create('keyword');

    return MappingDefinition::create()
      ->addProperty('id', FieldDefinition::create('integer'))
      ->addProperty('uuid', $keyword_field)
      ->addProperty('title', FieldDefinition::create('text'))
      ->addProperty('status', FieldDefinition::create('boolean'));
  }

}
