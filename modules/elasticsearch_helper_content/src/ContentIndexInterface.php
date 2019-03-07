<?php

namespace Drupal\elasticsearch_helper_content;

/**
 * Interface ContentIndexInterface
 */
interface ContentIndexInterface {

  /**
   * Returns index configuration.
   *
   * @return array
   */
  public function getIndexConfiguration();

  /**
   * Returns bundle configuration.
   *
   * @param $entity_type_id
   * @param $bundle
   *
   * @return array
   */
  public function getBundleConfiguration($entity_type_id, $bundle);

  /**
   * Returns configuration name.
   *
   * @return string
   */
  public function getConfigName();

}
