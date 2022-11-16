<?php

namespace Drupal\elasticsearch_helper_example\Plugin\ElasticsearchIndex;

/**
 * @ElasticsearchIndex(
 *   id = "example_simple_node_index",
 *   label = @Translation("Example simple node index"),
 *   indexName = "example-simple",
 *   entityType = "node"
 * )
 */
class ExampleSimpleNodeIndex extends IndexBase {

}
