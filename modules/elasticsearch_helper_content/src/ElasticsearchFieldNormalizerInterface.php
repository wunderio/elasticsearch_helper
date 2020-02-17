<?php

namespace Drupal\elasticsearch_helper_content;

/**
 * Defines interface for Elasticsearch field normalizer plugins.
 */
interface ElasticsearchFieldNormalizerInterface extends ElasticsearchNormalizerInterface {

  /**
   * {@inheritdoc}
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   * @param \Drupal\Core\Field\FieldItemListInterface|null $field
   */
  public function normalize($entity, $field, array $context = []);

}
