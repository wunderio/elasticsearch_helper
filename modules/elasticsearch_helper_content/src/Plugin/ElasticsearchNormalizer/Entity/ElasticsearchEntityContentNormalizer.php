<?php

namespace Drupal\elasticsearch_helper_content\Plugin\ElasticsearchNormalizer\Entity;

use Drupal\elasticsearch_helper_content\ElasticsearchEntityContentNormalizerInterface;
use Drupal\elasticsearch_helper_content\ElasticsearchEntityNormalizerBase;

/**
 * @ElasticsearchEntityNormalizer(
 *   id = "content",
 *   label = @Translation("Content"),
 *   weight = 0
 * )
 */
class ElasticsearchEntityContentNormalizer extends ElasticsearchEntityNormalizerBase implements ElasticsearchEntityContentNormalizerInterface {

  /**
   * {@inheritdoc}
   */
  public function getPropertyDefinitions(array $context = []) {
    return [];
  }

}
