<?php

namespace Drupal\elasticsearch_helper\Elasticsearch\Index;

use Drupal\elasticsearch_helper\Elasticsearch\DefinitionBase;
use Drupal\elasticsearch_helper\Elasticsearch\ObjectTrait;

/**
 * Index definition provides an unified way to describe index structure.
 *
 * Example:
 *
 *   Elasticsearch index plugins must return mapping definition using
 *   $plugin->getIndexMappings() method.
 *
 *     $mappings = $this->getIndexMappings();
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
 * @see \Drupal\elasticsearch_helper\Elasticsearch\Index\MappingsDefinition
 * @see \Drupal\elasticsearch_helper\Elasticsearch\Index\SettingsDefinition
 */
class IndexDefinition extends DefinitionBase {

  use ObjectTrait;

  /**
   * Index mappings.
   *
   * @var \Drupal\elasticsearch_helper\Elasticsearch\Index\MappingsDefinition
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
   * @param \Drupal\elasticsearch_helper\Elasticsearch\Index\MappingsDefinition $mappings
   *
   * @return self
   */
  public function setMappings(MappingsDefinition $mappings) {
    $this->mappings = $mappings;

    return $this;
  }

  /**
   * Returns mappings definition instance.
   *
   * @return \Drupal\elasticsearch_helper\Elasticsearch\Index\MappingsDefinition
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

    /** @var \Drupal\elasticsearch_helper\Elasticsearch\DefinitionBase[] $parts */
    $parts = [
      'mappings' => $this->getMappings(),
      'settings' => $this->getSettings(),
    ];

    foreach ($parts as $name => $definition) {
      if ($definition) {
        $result[$name] = $definition->toArray();
      }
    }

    return $result;
  }

}
