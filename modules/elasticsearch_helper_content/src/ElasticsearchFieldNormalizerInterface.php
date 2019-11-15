<?php

namespace Drupal\elasticsearch_helper_content;

/**
 * Defines interface for Elasticsearch field normalizer plugins.
 */
interface ElasticsearchFieldNormalizerInterface extends ElasticsearchNormalizerInterface {

  /**
   * {@inheritdoc}
   *
   * @param \Drupal\Core\Field\FieldItemListInterface|null $object
   */
  public function normalize($object, array $context = []);

}
