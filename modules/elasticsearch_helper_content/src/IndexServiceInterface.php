<?php

namespace Drupal\elasticsearch_helper_content;

/**
 * Interface IndexServiceInterface
 */
interface IndexServiceInterface {

  /**
   * Returns index configuration for entity type bundle.
   *
   * @param $entity_type
   * @param $bundle
   *
   * @return array
   */
  public function getBundleConfiguration($entity_type, $bundle);

}
