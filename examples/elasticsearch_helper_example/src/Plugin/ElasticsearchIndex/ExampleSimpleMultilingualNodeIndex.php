<?php

namespace Drupal\elasticsearch_helper_example\Plugin\ElasticsearchIndex;

/**
 * @ElasticsearchIndex(
 *   id = "example_simple_multilingual_node_index",
 *   label = @Translation("Example multilingual simple node index"),
 *   indexName = "example-simple-multilingual",
 *   entityType = "node",
 *   multilingual = {
 *     "index_pattern" = "{indexName}-{langcode}",
 *     "exclude" = {}
 *   }
 * )
 */
class ExampleSimpleMultilingualNodeIndex extends IndexBase {

}
