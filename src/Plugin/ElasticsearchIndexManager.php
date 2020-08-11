<?php

namespace Drupal\elasticsearch_helper\Plugin;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\Plugin\DefaultPluginManager;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Queue\QueueFactory;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Elasticsearch\Common\Exceptions\ElasticsearchException;

/**
 * Provides the Elasticsearch index plugin manager.
 */
class ElasticsearchIndexManager extends DefaultPluginManager {

  use StringTranslationTrait;

  /**
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * @var \Drupal\Core\Queue\QueueWorkerInterface
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
   * Indexes the entity into any matching indices.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *
   * @throws \Drupal\Component\Plugin\Exception\PluginException
   */
  public function indexEntity(EntityInterface $entity) {
    foreach ($this->getDefinitions() as $plugin) {
      if (isset($plugin['entityType']) && $entity->getEntityTypeId() == $plugin['entityType']) {
        if (!empty($plugin['bundle']) && $plugin['bundle'] != $entity->bundle()) {
          // Do not index if defined plugin bundle differs from entity bundle.
          continue;
        }

        try {
          // Index the entity in elasticsearch.
          $this->createInstance($plugin['id'])->index($entity);
        }
        catch (ElasticsearchException $e) {
          $this->logger->error('Elasticsearch indexing failed: @message', [
            '@message' => $e->getMessage(),
          ]);

          // TODO: queue for later indexing.
        }
      }
    }
  }

  /**
   * Deletes the entity from any matching indices.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *
   * @throws \Drupal\Component\Plugin\Exception\PluginException
   */
  public function deleteEntity(EntityInterface $entity) {
    foreach ($this->getDefinitions() as $plugin) {
      if (isset($plugin['entityType']) && $entity->getEntityTypeId() == $plugin['entityType']) {
        if (!empty($plugin['bundle']) && $plugin['bundle'] != $entity->bundle()) {
          // Do not delete if defined plugin bundle differs from entity bundle.
          continue;
        }

        try {
          // Delete the entity from elasticsearch.
          $this->createInstance($plugin['id'])->delete($entity);
        }
        catch (ElasticsearchException $e) {
          $this->logger->error('Elasticsearch deletion failed: @message', [
            '@message' => $e->getMessage(),
          ]);

          // TODO: queue for later indexing.
        }
      }
    }
  }

  /**
   * Re-indexes the content managed by Elasticsearch index plugins.
   *
   * @param array $indices
   * @param array $context
   *
   * @throws \Drupal\Component\Plugin\Exception\PluginException
   * @throws \Exception
   */
  public function reindex($indices = [], array $context = []) {
    foreach ($this->getDefinitions() as $definition) {
      if (empty($indices) || in_array($definition['id'], $indices)) {
        /** @var \Drupal\elasticsearch_helper\Plugin\ElasticsearchIndexInterface $plugin */
        $plugin = $this->createInstance($definition['id']);
        $plugin->reindex($context);
      }
    }
  }

  /**
   * Queues all entities of given entity type for re-indexing.
   *
   * @param string $entity_type
   * @param string null $bundle
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function reindexEntities($entity_type, $bundle = NULL) {
    $query = $this->entityTypeManager->getStorage($entity_type)->getQuery();

    if ($bundle) {
      $entity_type_instance = $this->entityTypeManager->getDefinition($entity_type);
      $query->condition($entity_type_instance->getKey('bundle'), $bundle);
    }

    $result = $query->execute();

    foreach ($result as $entity_id) {
      $this->queue->createItem([
        'entity_type' => $entity_type,
        'entity_id' => $entity_id,
      ]);
    }

    $t_args = ['@type' => $entity_type . ($bundle ? ':' . $bundle : '')];

    $this->logger->notice($this->t('Entities of type "@type" will be indexed on the next cron run.', $t_args));
  }

}
