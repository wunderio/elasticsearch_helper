<?php

namespace Drupal\elasticsearch_helper\Plugin;

use Drupal\Core\Config\ConfigFactory;
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
   * @var \Drupal\Core\Config\Config|\Drupal\Core\Config\ImmutableConfig
   */
  protected $config;

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
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   Entity type manager instance.
   * @param \Drupal\Core\Queue\QueueFactory $queue_factory
   *   Queue factory.
   * @param \Drupal\Core\Logger\LoggerChannelFactoryInterface $logger_factory
   *   Logger factory.
   * @param \Drupal\Core\Config\ConfigFactory $config_factory
   *   Config factory service.
   */
  public function __construct(\Traversable $namespaces, CacheBackendInterface $cache_backend, ModuleHandlerInterface $module_handler, EntityTypeManagerInterface $entity_type_manager, QueueFactory $queue_factory, LoggerChannelFactoryInterface $logger_factory, ConfigFactory $config_factory) {
    parent::__construct('Plugin/ElasticsearchIndex', $namespaces, $module_handler, 'Drupal\elasticsearch_helper\Plugin\ElasticsearchIndexInterface', 'Drupal\elasticsearch_helper\Annotation\ElasticsearchIndex');

    $this->alterInfo('elasticsearch_helper_elasticsearch_index_info');
    $this->setCacheBackend($cache_backend, 'elasticsearch_helper_elasticsearch_index_plugins');
    $this->entityTypeManager = $entity_type_manager;
    $this->queue = $queue_factory->get('elasticsearch_helper_indexing');
    $this->logger = $logger_factory->get('elasticsearch_helper');
    $this->config = $config_factory->get('elasticsearch_helper.settings');
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
      if (!$this->indexIsEnabled($plugin['id'])) {
        continue;
      }

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
      if (!$this->indexIsEnabled($plugin['id'])) {
        continue;
      }

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
      if (!$this->indexIsEnabled($definition['id'])) {
        continue;
      }

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
   * @param $entity_type
   * @param $bundle
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

    // Allow other modules to alter the entity query prior to execution.
    $this->moduleHandler->alter('elasticsearch_helper_reindex_entity_query', $query, $entity_type, $bundle);

    // Execute the entity query.
    $result = $query->execute();

    // Queue entities for reindexing.
    foreach ($result as $entity_id) {
      $this->addToQueue($entity_type, $entity_id);
    }

    $t_args = ['@type' => $entity_type . ($bundle ? ':' . $bundle : '')];

    $this->logger->notice($this->t('Entities of type "@type" will be indexed on the next cron run.', $t_args));
  }

  /**
   * Adds the entity to queue for indexing.
   *
   * @param $entity_type
   * @param $entity_id
   */
  public function addToQueue($entity_type, $entity_id) {
    $this->queue->createItem([
      'entity_type' => $entity_type,
      'entity_id' => $entity_id,
    ]);
  }

  /**
   * Check whether an index is enabled according to configuration.
   *
   * @param string $index
   *   Id of the index to check.
   *
   * @return bool
   *   Whether the index should be considered enabled.
   */
  public function indexIsEnabled($index) {
    $index_statuses = $this->config->get('index_statuses');

    // When an index is not yet known in configuration, we default to enabling
    // it.
    return isset($index_statuses[$index]) ? $index_statuses[$index] : TRUE;
  }

}
