<?php

namespace Drupal\elasticsearch_helper\Elasticsearch\Index;

use Drupal\elasticsearch_helper\Plugin\ElasticsearchIndexInterface;

/**
 * Provides support for legacy "type" parameter.
 *
 * @deprecated Will be removed from the codebase when support for
 *   Elasticsearch 6 is removed.
 */
trait TypeTrait {

  /**
   * @var string
   */
  protected $type = ElasticsearchIndexInterface::TYPE_DEFAULT;

  /**
   * Sets index type.
   *
   * @param $type
   */
  public function setType($type) {
    $this->type = $type;
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
