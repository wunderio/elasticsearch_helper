<?php

namespace Drupal\elasticsearch_helper_content;

use Drupal\Component\Plugin\PluginManagerInterface;

/**
 * Elasticsearch field normalizer plugin manager interface.
 */
interface ElasticsearchFieldNormalizerManagerInterface extends PluginManagerInterface {

  /**
   * Gets the definition of all plugins that support given field type.
   *
   * @param $type
   *
   * @return mixed[]
   *   An array of plugin definitions (empty array if no definitions were
   *   found). Keys are plugin IDs.
   */
  public function getDefinitionsByFieldType($type);

}
