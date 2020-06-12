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
 *       ->setMappings($mappings)
 *       ->setSettings($settings);
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
   * Index mappings.
   *
   * @var \Drupal\elasticsearch_helper\Elasticsearch\Index\MappingDefinition
   */
  protected $mappings;

  /**
   * Index settings.
   *
   * @var \Drupal\elasticsearch_helper\Elasticsearch\Index\SettingsDefinition
   */
  protected $settings;

  /**
   * Sets mappings definition.
   *
   * @param \Drupal\elasticsearch_helper\Elasticsearch\Index\MappingDefinition $mappings
   *
   * @return self
   */
  public function setMappings(MappingDefinition $mappings) {
    $this->mappings = $mappings;

    return $this;
  }

  /**
   * Returns mappings definition instance.
   *
   * @return \Drupal\elasticsearch_helper\Elasticsearch\Index\MappingDefinition
   */
  public function getMappings() {
    return $this->mappings;
  }

  /**
   * Sets index settings.
   *
   * @param \Drupal\elasticsearch_helper\Elasticsearch\Index\SettingsDefinition $settings
   *
   * @return self
   */
  public function setSettings(SettingsDefinition $settings) {
    $this->settings = $settings;

    return $this;
  }

  /**
   * Returns index settings.
   *
   * @return \Drupal\elasticsearch_helper\Elasticsearch\Index\SettingsDefinition
   */
  public function getSettings() {
    return $this->settings;
  }

  /**
   * Returns index definition as an array.
   *
   * @return array
   */
  public function toArray() {
    $result = $this->getOptions();

    if ($settings = $this->getSettings()) {
      $result['settings'] = $settings->toArray();
    }

    if ($mappings = $this->getMappings()) {
      $mappings_array = $mappings->toArray();

      if (ElasticsearchClientVersion::getMajorVersion() < 7) {
        $result['mappings'][$this->getType()] = $mappings_array;
      }
      else {
        $result['mappings'] = $mappings_array;
      }
    }

    return $result;
  }

}
