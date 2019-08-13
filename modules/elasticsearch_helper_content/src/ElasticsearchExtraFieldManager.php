<?php

namespace Drupal\elasticsearch_helper_content;

use Drupal\Component\Plugin\Exception\PluginException;
use Drupal\Core\Plugin\DefaultPluginManager;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;

/**
 * Provides the Elasticsearch extra field plugin manager.
 */
class ElasticsearchExtraFieldManager extends DefaultPluginManager {

  /**
   * Constructs a new ElasticsearchFieldManager object.
   *
   * @param \Traversable $namespaces
   *   An object that implements \Traversable which contains the root paths
   *   keyed by the corresponding namespace to look for plugin implementations.
   * @param \Drupal\Core\Cache\CacheBackendInterface $cache_backend
   *   Cache backend instance to use.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler to invoke the alter hook with.
   */
  public function __construct(\Traversable $namespaces, CacheBackendInterface $cache_backend, ModuleHandlerInterface $module_handler) {
    parent::__construct('Plugin/ElasticsearchExtraField', $namespaces, $module_handler, 'Drupal\elasticsearch_helper_content\ElasticsearchExtraFieldInterface', 'Drupal\elasticsearch_helper_content\Annotation\ElasticsearchExtraField');

    $this->alterInfo('elasticsearch_extra_field_info');
    $this->setCacheBackend($cache_backend, 'elasticsearch_extra_field_plugins');
  }

  /**
   * Returns a list of Elasticsearch extra fields.
   *
   * @return \Drupal\elasticsearch_helper_content\ElasticsearchField[]
   */
  public function getExtraFields() {
    static $result = NULL;

    if (is_null($result)) {
      $result = [];

      foreach ($this->getDefinitions() as $plugin_id => $definition) {
        try {
          if ($instance = $this->createInstance($plugin_id)) {
            /** @var \Drupal\elasticsearch_helper_content\ElasticsearchField[] $extra_fields */
            if ($extra_fields = $instance->getFields()) {
              foreach ($extra_fields as $extra_field) {
                $result[$extra_field->getName()] = $extra_field;
              }
            }
          }
        }
        catch (PluginException $e) {
        }
      }
    }

    return $result;
  }

}
