<?php

namespace Drupal\elasticsearch_helper\Elasticsearch\Index;

/**
 * Provides support for legacy "type" parameter.
 *
 * @deprecated Will be removed from the codebase when support for Elasticsearch
 *   is dropped.
 */
trait TypeTrait {

  /**
   * @var string
   */
  protected $type = '_doc';

  /**
   * Sets index type.
   *
   * @param $type
   */
  public function setType($type) {
    $this->type;
  }

  /**
   * Returns index type.
   *
   * @return string
   */
  public function getType() {
    return $this->type;
  }

}
