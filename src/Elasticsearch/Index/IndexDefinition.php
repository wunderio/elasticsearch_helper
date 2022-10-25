<?php

namespace Drupal\elasticsearch_helper\Elasticsearch\Index;

use Drupal\elasticsearch_helper\Elasticsearch\DefinitionBase;
use Drupal\elasticsearch_helper\Elasticsearch\ObjectTrait;
use Drupal\elasticsearch_helper\ElasticsearchClientVersion;

/**
 * Index definition provides an unified way to describe index structure.
 *
 * Example:
 *
 *   Elasticsearch index plugins must return mapping definition using
 *   $plugin->getMappingDefinition() method.
 *
 *     $mappings = $this->getMappingDefinition();
 *
 *   Index settings definition can be provided using the following code:
 *
 *     $settings = SettingsDefinition::create()
 *       ->addOptions([
 *         'number_of_shards' => 1,
 *         'number_of_replicas' => 0,
 *     ]);
 *
 *   Index definition uses both mappings and settings definitions to define
 *   an index.
 *
 *     $index_definition = IndexDefinition::create()
 *       ->setMappingDefinition($mappings)
 *       ->setSettingsDefinition($settings);
 *
 *   If Elasticsearch index plugin returns index definition in
 *   $plugin->getIndexDefinition() method, method $plugin->setup() will be
 *   able to set-up an index using provided mapping and settings.
 *
 * @see \Drupal\elasticsearch_helper\Elasticsearch\Index\MappingDefinition
 * @see \Drupal\elasticsearch_helper\Elasticsearch\Index\SettingsDefinition
 */
class IndexDefinition extends DefinitionBase {

  use ObjectTrait;
  use TypeTrait;

  /**
   * Index mapping definition.
   *
   * @var \Drupal\elasticsearch_helper\Elasticsearch\Index\MappingDefinition
   */
  protected $mappingDefinition;

  /**
   * Index settings definition.
   *
   * @var \Drupal\elasticsearch_helper\Elasticsearch\Index\SettingsDefinition
   */
  protected $settingsDefinition;

  /**
   * Sets mapping definition.
   *
   * @param \Drupal\elasticsearch_helper\Elasticsearch\Index\MappingDefinition $mapping_definition
   *
   * @return self
   */
  public function setMappingDefinition(MappingDefinition $mapping_definition) {
    $this->mappingDefinition = $mapping_definition;

    return $this;
  }

  /**
   * Returns mapping definition instance.
   *
   * @return \Drupal\elasticsearch_helper\Elasticsearch\Index\MappingDefinition
   */
  public function getMappingDefinition() {
    return $this->mappingDefinition;
  }

  /**
   * Sets index settings definition.
   *
   * @param \Drupal\elasticsearch_helper\Elasticsearch\Index\SettingsDefinition $settings_definition
   *
   * @return self
   */
  public function setSettingsDefinition(SettingsDefinition $settings_definition) {
    $this->settingsDefinition = $settings_definition;

    return $this;
  }

  /**
   * Returns index settings definition instance.
   *
   * @return \Drupal\elasticsearch_helper\Elasticsearch\Index\SettingsDefinition
   */
  public function getSettingsDefinition() {
    return $this->settingsDefinition;
  }

  /**
   * Returns index definition as an array.
   *
   * @return array
   */
  public function toArray() {
    $result = $this->getOptions();

    if ($settings = $this->getSettingsDefinition()) {
      $result['settings'] = $settings->toArray();
    }

    if ($mappings = $this->getMappingDefinition()) {
      $result['mappings'] = $mappings->toArray();
    }

    return $result;
  }

}
