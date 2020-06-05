<?php

namespace Drupal\elasticsearch_helper_test\Plugin\ElasticsearchIndex;

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
  public function setup() {
    $index_name = $this->getIndexName([]);

    if (!$this->client->indices()->exists(['index' => $index_name])) {
      $this->client->indices()->create([
        'index' => $index_name,
        'body' => [
          'number_of_shards' => 1,
          'number_of_replicas' => 0,
        ],
      ]);

      $mapping = $this->getMapping();
      $this->client->indices()->putMapping($mapping);
    }
  }

  /**
   * Returns field mapping.
   *
   * @return array
   */
  protected function getMapping() {
    return [
      'index' => $this->getIndexName([]),
      'type' => $this->getTypeName([]),
      'body' => [
        'properties' => [
          'id' => [
            'type' => 'keyword',
          ],
          'uuid' => [
            'type' => 'keyword',
          ],
          'title' => [
            'type' => 'text',
          ],
          'status' => [
            'type' => 'keyword',
          ],
        ],
      ],
    ];
  }

}
