<?php

namespace Drupal\elasticsearch_helper_test\Plugin\ElasticsearchIndex;

use Drupal\elasticsearch_helper_example\Plugin\ElasticsearchIndex\ExampleMultilingualContentIndex;

/**
 * @ElasticsearchIndex(
 *   id = "test_multilingual_node_index",
 *   label = @Translation("Test multilingual node index"),
 *   indexName = "test-multilingual-node-index-{langcode}",
 *   entityType = "node"
 * )
 */
class TestMultilingualNodeIndex extends ExampleMultilingualContentIndex {

}
