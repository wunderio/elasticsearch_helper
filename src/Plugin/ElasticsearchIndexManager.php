<?php

namespace Drupal\elasticsearch_helper\Plugin;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\Plugin\DefaultPluginManager;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Queue\QueueFactory;
use Drupal\Core\Queue\QueueInterface;
use Drupal\Core\Queue\QueueWorkerInterface;
use Drupal\Core\Queue\QueueWorkerManagerInterface;
use Psr\Log\LoggerInterface;

/**
 * Provides the Elasticsearch index plugin manager.
 */
class ElasticsearchIndexManager extends DefaultPluginManager {

  /**
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * @var QueueWorkerInterface
   */
  protected $queue;

  /**
   * @var \Drupal\Core\Logger\LoggerChannelInterface
   */
  protected $logger;

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
  public function __construct(\Traversable $namespaces, CacheBackendInterface $cache_backend, ModuleHandlerInterface $module_handler, EntityTypeManagerInterface $entity_type_manager, QueueFactory $queue_factory, LoggerChannelFactoryInterface $logger_factory) {
    parent::__construct('Plugin/ElasticsearchIndex', $namespaces, $module_handler, 'Drupal\elasticsearch_helper\Plugin\ElasticsearchIndexInterface', 'Drupal\elasticsearch_helper\Annotation\ElasticsearchIndex');

    $this->alterInfo('elasticsearch_helper_elasticsearch_index_info');
    $this->setCacheBackend($cache_backend, 'elasticsearch_helper_elasticsearch_index_plugins');
    $this->entityTypeManager = $entity_type_manager;
    $this->queue = $queue_factory->get('elasticsearch_helper_indexing');
    $this->logger = $logger_factory->get('elasticsearch_helper');
  }

  /**
   * Index an entity into any matching indices.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   */
  public function indexEntity(EntityInterface $entity) {
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
   * @param \Drupal\Core\Entity\EntityInterface $entity
   */
  public function deleteEntity(EntityInterface $entity) {
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

  /**
   * Reindex elasticsearch with all entities.
   * @param $indices
   */
  public function reindex($indices = array()) {

    foreach ($this->getDefinitions() as $plugin) {
      if (empty($indices) || in_array($plugin['id'], $indices)) {

        if ($plugin['entityType']) {
          $query = $this->entityTypeManager->getStorage($plugin['entityType'])->getQuery();

          $entity_type = $this->entityTypeManager->getDefinition($plugin['entityType']);

          if ($plugin['bundle']) {
            $query->condition($entity_type->getKey('bundle'), $plugin['bundle']);
          }

          $result = $query->execute();

          foreach ($result as $entity_id) {
            $this->queue->createItem([
              'entity_type' => $entity_type->id(),
              'entity_id' => $entity_id,
            ]);
          }
          $this->logger->notice("Marked indices to be reindex on next cronrun");
        }
      }
    }
  }

}
