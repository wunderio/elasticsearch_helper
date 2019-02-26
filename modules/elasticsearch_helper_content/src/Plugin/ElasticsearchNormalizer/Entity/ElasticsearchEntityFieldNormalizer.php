<?php

namespace Drupal\elasticsearch_helper_content\Plugin\ElasticsearchNormalizer\Entity;

use Drupal\elasticsearch_helper_content\ElasticsearchEntityFieldNormalizerInterface;
use Drupal\elasticsearch_helper_content\ElasticsearchEntityNormalizerBase;

/**
 * @ElasticsearchEntityNormalizer(
 *   id = "field",
 *   label = @Translation("Field"),
 *   weight = 5
 * )
 */
class ElasticsearchEntityFieldNormalizer extends ElasticsearchEntityNormalizerBase implements ElasticsearchEntityFieldNormalizerInterface {

  /**
   * {@inheritdoc}
   */
  public function getPropertyDefinitions(array $context = []) {
    return [];
  }

}
