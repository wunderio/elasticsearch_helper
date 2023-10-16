<?php

namespace Drupal\elasticsearch_helper_example\Plugin\ElasticsearchIndex;

use Drupal\elasticsearch_helper\Elasticsearch\Index\FieldDefinition;
use Drupal\elasticsearch_helper\Elasticsearch\Index\MappingDefinition;
use Drupal\elasticsearch_helper\Plugin\ElasticsearchIndexBase;

/**
 * An example index that uses caching.
 *
 * Cached indexes caches serialized data to speed up re-indexing processes.
 *
 * @ElasticsearchIndex(
 *   id = "cached_node_index",
 *   label = @Translation("Cached node index"),
 *   indexName = "cached",
 *   cache = TRUE,
 *   typeName = "node",
 *   entityType = "node"
 * )
 */
class NodeIndexCached extends ElasticsearchIndexBase {

  /**
   * {@inheritdoc}
   */
  public function getMappingDefinition(array $context = []) {
    $user_property = FieldDefinition::create('object')
      ->addProperty('uid', FieldDefinition::create('integer'))
      ->addProperty('name', FieldDefinition::create('keyword'));

    return MappingDefinition::create()
      ->addProperty('id', FieldDefinition::create('integer'))
      ->addProperty('uuid', FieldDefinition::create('keyword'))
      ->addProperty('title', FieldDefinition::create('text'))
      ->addProperty('status', FieldDefinition::create('keyword'))
      ->addProperty('user', $user_property);
  }
}
