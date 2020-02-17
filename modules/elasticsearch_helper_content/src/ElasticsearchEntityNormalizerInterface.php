<?php

namespace Drupal\elasticsearch_helper_content;

/**
 * Defines interface for Elasticsearch field normalizer plugins.
 */
interface ElasticsearchEntityNormalizerInterface extends ElasticsearchNormalizerInterface {

  /**
   * Normalizes an object into a set of arrays/scalars.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   * @param array $context
   *
   * @return array|string|int|float|bool
   */
  public function normalize($entity, array $context = []);

}
