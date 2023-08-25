<?php

namespace Drupal\elasticsearch_helper_test\Plugin\ElasticsearchIndex;

use Drupal\elasticsearch_helper_example\Plugin\ElasticsearchIndex\ExampleSimpleNodeIndex;

/**
 * @ElasticsearchIndex(
 *   id = "test_simple_node_index",
 *   label = @Translation("Test simple node index"),
 *   indexName = "test-simple",
 *   entityType = "node"
 * )
 */
class TestSimpleNodeIndex extends ExampleSimpleNodeIndex {

}
