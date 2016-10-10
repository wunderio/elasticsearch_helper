<?php

namespace Drupal\elasticsearch_helper\Plugin;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Plugin\DefaultPluginManager;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;

/**
 * Provides the Elasticsearch index plugin manager.
 */
class ElasticsearchIndexManager extends DefaultPluginManager {

  /**
   * Constructor for ElasticsearchIndexManager objects.
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
    parent::__construct('Plugin/ElasticsearchIndex', $namespaces, $module_handler, 'Drupal\elasticsearch_helper\Plugin\ElasticsearchIndexInterface', 'Drupal\elasticsearch_helper\Annotation\ElasticsearchIndex');

    $this->alterInfo('elasticsearch_helper_elasticsearch_index_info');
    $this->setCacheBackend($cache_backend, 'elasticsearch_helper_elasticsearch_index_plugins');
  }

  /**
   * Index an entity into any matching indices.
   *
   * @param \Drupal\Core\Entity\ContentEntityInterface $entity
   */
  public function indexEntity(ContentEntityInterface $entity) {
    foreach ($this->getDefinitions() as $plugin) {
      if (isset($plugin['entityType']) && $entity->getEntityTypeId() == $plugin['entityType']) {
        if (!empty($plugin['bundle']) && $plugin['bundle'] != $entity->bundle()) {
          // Do not index if defined plugin bundle differs from entity bundle.
          continue;
        }
        // Index the entity in elasticsearch.
        $this->createInstance($plugin['id'])->index($entity);
      }
    }
  }

  /**
   * Delete an entity from any matching indices.
   *
   * @param \Drupal\Core\Entity\ContentEntityInterface $entity
   */
  public function deleteEntity(ContentEntityInterface $entity) {
    foreach ($this->getDefinitions() as $plugin) {
      if (isset($plugin['entityType']) && $entity->getEntityTypeId() == $plugin['entityType']) {
        if (!empty($plugin['bundle']) && $plugin['bundle'] != $entity->bundle()) {
          // Do not delete if defined plugin bundle differs from entity bundle.
          continue;
        }
        // Index the entity in elasticsearch.
        $this->createInstance($plugin['id'])->delete($entity);
      }
    }
  }

}
