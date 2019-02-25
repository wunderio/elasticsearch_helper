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
  public function getConfiguration();

  /**
   * Returns configuration name.
   *
   * @return string
   */
  public function getConfigName();

}
