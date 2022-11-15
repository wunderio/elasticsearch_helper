<?php

namespace Drupal\elasticsearch_helper_example\Plugin\ElasticsearchIndex;

use Drupal\elasticsearch_helper\Plugin\ElasticsearchIndexBase;
use Drupal\elasticsearch_helper_example\Plugin\Normalizer\NodeNormalizer;

/**
 * Index base class.
 */
abstract class IndexBase extends ElasticsearchIndexBase {

  /**
   * Returns node normalizer.
   *
   * @return \Drupal\elasticsearch_helper_example\Plugin\Normalizer\NodeNormalizer
   */
  protected function getNormalizer() {
    return new NodeNormalizer(
      \Drupal::service('entity_type.manager'),
      \Drupal::service('entity_type.repository'),
      \Drupal::service('entity_field.manager')
    );
  }

  /**
   * {@inheritdoc}
   *
   * @param \Drupal\node\Entity\Node $source
   *
   * @see self::getNormalizer()
   */
  public function serialize($source, $context = []) {
    // Typically, normalizers are handled by serializer service where
    // conforming normalizer is picked by the means of weight. In this case
    // a specific normalizer class is used to normalize the object.
    $data = $this->getNormalizer()->normalize($source, 'elasticsearch_helper', $context);

    return $data;
  }

}
