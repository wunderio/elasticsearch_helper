<?php

namespace Drupal\elasticsearch_helper_test\Plugin\ElasticsearchIndex;

use Drupal\elasticsearch_helper\Elasticsearch\Index\FieldDefinition;
use Drupal\elasticsearch_helper\Elasticsearch\Index\IndexDefinition;
use Drupal\elasticsearch_helper\Elasticsearch\Index\MappingDefinition;
use Drupal\elasticsearch_helper\Elasticsearch\Index\SettingsDefinition;
use Drupal\elasticsearch_helper\Plugin\ElasticsearchIndexBase;

/**
 * @ElasticsearchIndex(
 *   id = "simple_node_index",
 *   label = @Translation("Simple Node Index"),
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

    return IndexDefinition::create()
      ->setMappings($mappings)
      ->setSettings($settings);
  }

  /**
   * {@inheritdoc}
   */
  public function getIndexMappings() {
    $keyword_field = FieldDefinition::create('keyword');

    return MappingDefinition::create()
      ->addProperty('id', FieldDefinition::create('integer'))
      ->addProperty('uuid', $keyword_field)
      ->addProperty('title', FieldDefinition::create('text'))
      ->addProperty('status', $keyword_field);
  }

}
