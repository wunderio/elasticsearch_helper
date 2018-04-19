<?php

namespace Drupal\elasticsearch_helper_content\Plugin\ElasticsearchIndex;

/**
 * @ElasticsearchIndex(
 *   id = "content_index_node",
 *   label = @Translation("Node Index (Multilingual)"),
 *   indexName = "content-node-{langcode}",
 *   typeName = "node",
 *   entityType = "node"
 * )
 */
class NodeIndex extends MultilingualContentIndex {

  use AlterableIndexTrait;

  /**
   * NOTE:
   *
   * The structure of the indexed data is determined by normalizers,
   * see NodeNormalizer.php.
   *
   * The functionality for multilingual indexing is provided by
   * MultilingualContentIndex.
   *
   */
}
