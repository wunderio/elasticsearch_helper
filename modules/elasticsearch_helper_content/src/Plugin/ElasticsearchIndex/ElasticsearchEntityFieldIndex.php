<?php

namespace Drupal\elasticsearch_helper_content\Plugin\ElasticsearchIndex;

use Drupal\elasticsearch_helper_content\ElasticsearchEntityNormalizerBase;

/**
 * Class ElasticsearchEntityFieldIndex
 */
class ElasticsearchEntityFieldIndex extends ElasticsearchEntityNormalizerBase {

  /**
   * {@inheritdoc}
   */
  public function getPropertyDefinitions(array $context = []) {
    return [];
  }
}
