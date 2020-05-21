<?php

namespace Drupal\elasticsearch_helper_content\Plugin\ElasticsearchIndex;

use Drupal\elasticsearch_helper\Plugin\ElasticsearchIndexBase;

/**
 * @ElasticsearchIndex(
 *   id = "content_index_user",
 *   label = @Translation("User Index"),
 *   indexName = "content-user",
 *   entityType = "user"
 * )
 */
class UserIndex extends ElasticsearchIndexBase {

  use AlterableIndexTrait;

  /**
   * NOTE: The structure of the indexed data is determined by normalizers,
   * see NodeNormalizer.php.
   */

  /**
   * @inheritdoc
   */

}
