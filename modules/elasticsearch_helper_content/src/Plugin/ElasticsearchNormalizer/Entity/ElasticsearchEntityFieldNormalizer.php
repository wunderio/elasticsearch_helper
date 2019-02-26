<?php

namespace Drupal\elasticsearch_helper_content\Plugin\ElasticsearchNormalizer\Entity;

use Drupal\elasticsearch_helper_content\ElasticsearchEntityFieldNormalizerInterface;
use Drupal\elasticsearch_helper_content\ElasticsearchEntityNormalizerBase;

/**
 * @ElasticsearchEntityNormalizer(
 *   id = "field",
 *   label = @Translation("Content entity field"),
 *   weight = 5
 * )
 */
class ElasticsearchEntityFieldNormalizer extends ElasticsearchEntityNormalizerBase implements ElasticsearchEntityFieldNormalizerInterface {

  /**
   * {@inheritdoc}
   */
  public function getPropertyDefinitions(array $context = []) {
    // Get core property definitions.
    $core_property_definitions = $this->getCorePropertyDefinitions($context);

    return array_merge($core_property_definitions, []);
  }

}
