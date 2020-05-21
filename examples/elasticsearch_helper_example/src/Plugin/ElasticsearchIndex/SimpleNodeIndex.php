<?php

namespace Drupal\elasticsearch_helper_example\Plugin\ElasticsearchIndex;

use Drupal\elasticsearch_helper\Plugin\ElasticsearchIndexBase;

/**
 * @ElasticsearchIndex(
 *   id = "simple_node_index",
 *   label = @Translation("Simple Node Index"),
 *   indexName = "simple",
 *   entityType = "node"
 * )
 */
class SimpleNodeIndex extends ElasticsearchIndexBase {

  /**
   * NOTE: The structure of the indexed data is determined by normalizers,
   * see NodeNormalizer.php.
   */
}
