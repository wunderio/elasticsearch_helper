<?php

namespace Drupal\elasticsearch_helper_example\Plugin\ElasticsearchIndex;

use Drupal\elasticsearch_helper\Elasticsearch\Index\FieldDefinition;
use Drupal\elasticsearch_helper\Elasticsearch\Index\IndexDefinition;
use Drupal\elasticsearch_helper\Elasticsearch\Index\MappingsDefinition;
use Drupal\elasticsearch_helper\Elasticsearch\Index\SettingsDefinition;
use Drupal\elasticsearch_helper\Plugin\ElasticsearchIndexBase;

/**
 * @ElasticsearchIndex(
 *   id = "simple_node_index",
 *   label = @Translation("Simple node index"),
 *   indexName = "simple",
 *   typeName = "node",
 *   entityType = "node"
 * )
 */
class SimpleNodeIndex extends ElasticsearchIndexBase {

  /**
   * {@inheritdoc}
   */
  public function getIndexDefinition() {
    // Get field mappings.
    $mappings = $this->getIndexMappings();

    // Get index settings.
    $settings = SettingsDefinition::create()
      ->addOptions([
        'number_of_shards' => 1,
        'number_of_replicas' => 0,
      ]);

    $index_definition = IndexDefinition::create()
      ->setMappings($mappings)
      ->setSettings($settings);

    // If you are using Elasticsearch < 7, add the type to the index definition.
    $index_definition->setType($this->getTypeName([]));

    return $index_definition;
  }

  /**
   * {@inheritdoc}
   */
  public function getIndexMappings() {
    $user_property = FieldDefinition::create('object')
      ->addProperty('id', FieldDefinition::create('integer'))
      ->addProperty('name', FieldDefinition::create('keyword'));

    return MappingsDefinition::create()
      ->addProperty('id', FieldDefinition::create('integer'))
      ->addProperty('uuid', FieldDefinition::create('keyword'))
      ->addProperty('title', FieldDefinition::create('text'))
      ->addProperty('status', FieldDefinition::create('keyword'))
      ->addProperty('user', $user_property);
  }
}
