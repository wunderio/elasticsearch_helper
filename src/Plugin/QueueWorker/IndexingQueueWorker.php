<?php

namespace Drupal\elasticsearch_helper\Plugin\QueueWorker;

use Drupal\Component\Utility\Bytes;
use Drupal\Component\Utility\Environment;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Queue\QueueWorkerBase;
use Drupal\elasticsearch_helper\Plugin\ElasticsearchIndexManager;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Index entities in Elasticsearch using a queue.
 *
 * @QueueWorker(
 *   id = "elasticsearch_helper_indexing",
 *   title = @Translation("Index entities in Elasticsearch"),
 *   cron = {"time" = 30}
 * )
 */
class IndexingQueueWorker extends QueueWorkerBase implements ContainerFactoryPluginInterface {

  /**
   * Name of the static global variable.
   *
   * @var string
   */
  public const QUEUE_INDEXING_VAR_NAME = 'ElasticsearchHelperQueueIndexing';

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  private $entityTypeManager;

  /**
   * The plugin manager for ElasticsearchIndex.
   *
   * @var \Drupal\elasticsearch_helper\Plugin\ElasticsearchIndexManager
   */
  private $elasticsearchIndexManager;

  /**
   * The config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  private $configFactory;

  /**
   * IndexingQueueWorker constructor.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\elasticsearch_helper\Plugin\ElasticsearchIndexManager $elasticsearch_index_manager
   *   The plugin manager for our ElasticsearchIndex plugins.
   */
  public function __construct(array $configuration, $plugin_id, array $plugin_definition, EntityTypeManagerInterface $entity_type_manager, ElasticsearchIndexManager $elasticsearch_index_manager, ConfigFactoryInterface $configFactory) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);

    $this->entityTypeManager = $entity_type_manager;
    $this->elasticsearchIndexManager = $elasticsearch_index_manager;
    $this->configFactory = $configFactory;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity_type.manager'),
      $container->get('plugin.manager.elasticsearch_index.processor'),
      $container->get('config.factory'),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function processItem($data) {
    $entity_type = $data['entity_type'];
    $entity_id = $data['entity_id'];

    $entity_storage = $this->entityTypeManager->getStorage($entity_type);

    if ($this->memoryConstrained()) {
      $entity_storage->resetCache();
    }

    $entity = $entity_storage->load($entity_id);

    if ($entity) {
      // Set a global static variable which could be used by other modules to
      // identify that the indexing is happening from the queue worker operation.
      $index_with_queue = &drupal_static(self::QUEUE_INDEXING_VAR_NAME);
      $index_with_queue = TRUE;

      // Index the entity.
      $this->elasticsearchIndexManager->indexEntity($entity);

      // Reset global static.
      drupal_static_reset(self::QUEUE_INDEXING_VAR_NAME);
    }
  }

  /**
   * Return whether memory is constrained based on a configurable safety margin.
   *
   * @return bool
   *   Whether the memory should be considered constrained.
   */
  private function memoryConstrained() {
    $margin = $this->configFactory->get('elasticsearch_helper')->get('memory_safety_margin');
    $margin = $margin ?: '16M';
    $current_usage = memory_get_usage();

    return !Environment::checkMemoryLimit($current_usage + Bytes::toNumber($margin));
  }

}
