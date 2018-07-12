<?php

namespace Drupal\elasticsearch_helper_content\Plugin\ElasticsearchIndex;

/**
 * @ElasticsearchIndex(
 *   id = "content",
 *   deriver = "Drupal\elasticsearch_helper_content\Plugin\Derivative\ElasticsearchIndex\ContentIndexDeriver"
 * )
 */
class ContentIndex extends MultilingualContentIndexBase {

  use AlterableIndexTrait;

  /**
   * NOTE:
   *
   * Specific index plugins are derived per entity id (and bundle).
   *
   * The structure of the indexed data is determined by normalizers,
   * see NodeNormalizer.php.
   *
   * The functionality for multilingual indexing is provided by
   * MultilingualContentIndex.
   *
   */
}
