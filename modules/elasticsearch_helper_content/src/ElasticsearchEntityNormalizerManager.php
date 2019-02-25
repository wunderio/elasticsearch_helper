<?php

namespace Drupal\elasticsearch_helper_content;

use Drupal\Core\Plugin\DefaultPluginManager;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;

/**
 * Provides the Elasticsearch entity normalizer plugin manager.
 */
class ElasticsearchEntityNormalizerManager extends DefaultPluginManager implements ElasticsearchEntityNormalizerManagerInterface {

  /**
   * Constructs a new ElasticsearchEntityNormalizerManager object.
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
    parent::__construct('Plugin/ElasticsearchNormalizer/Entity', $namespaces, $module_handler, 'Drupal\elasticsearch_helper_content\ElasticsearchNormalizerInterface', 'Drupal\elasticsearch_helper_content\Annotation\ElasticsearchEntityNormalizer');

    $this->alterInfo('elasticsearch_normalizer_entity_info');
    $this->setCacheBackend($cache_backend, 'elasticsearch_normalizer_entity_plugins');
  }

  public function getDefinitions() {
    $definitions = parent::getDefinitions();
    uasort($definitions, ['Drupal\Component\Utility\SortArray', 'sortByWeightElement']);

    return $definitions;
  }

}
