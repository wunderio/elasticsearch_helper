<?php

namespace Drupal\elasticsearch_helper_content;

use Drupal\Component\Plugin\PluginInspectionInterface;

/**
 * Defines an interface for Elasticsearch extra field plugins.
 */
interface ElasticsearchExtraFieldInterface extends PluginInspectionInterface {

  /**
   * Returns a list of Elasticsearch extra fields.
   *
   * @return \Drupal\elasticsearch_helper_content\ElasticsearchField[]
   */
  public function getFields();

}
