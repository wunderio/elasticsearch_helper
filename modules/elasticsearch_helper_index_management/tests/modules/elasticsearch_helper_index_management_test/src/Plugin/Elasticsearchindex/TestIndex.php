<?php

namespace Drupal\elasticsearch_helper_index_management_test\Plugin\ElasticsearchIndex;

use Drupal\elasticsearch_helper\Plugin\ElasticsearchIndexBase;

/**
 * @ElasticsearchIndex(
 *   id = "simple_test_node_index",
 *   label = @Translation("Simple Test Node Index"),
 *   indexName = "simple_test_node_index",
 *   typeName = "node",
 *   entityType = "node"
 * )
 */
class SimpleNodeIndex extends ElasticsearchIndexBase {
}
