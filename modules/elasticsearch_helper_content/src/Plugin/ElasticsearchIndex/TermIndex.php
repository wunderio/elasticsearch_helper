<?php

namespace Drupal\elasticsearch_helper_content\Plugin\ElasticsearchIndex;

/**
 * @ElasticsearchIndex(
 *   id = "content_index_term",
 *   label = @Translation("Topics Index (Multilingual)"),
 *   indexName = "content-topics-{langcode}",
 *   typeName = "taxonomy_term",
 *   entityType = "taxonomy_term",
 * )
 */
class TermIndex extends MultilingualContentIndex {

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
