<?php

namespace Drupal\elasticsearch_helper\Plugin\QueueWorker;

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
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  private $entity_type_manager;

  /**
   * @var \Drupal\elasticsearch_helper\Plugin\ElasticsearchIndexManager
   */
  private $elasticsearch_index_manager;

  /**
   * Constructs a new LocaleTranslation object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param array $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler.
   * @param \Drupal\Core\Queue\QueueInterface $queue
   *   The queue object.
   */
  public function __construct(array $configuration, $plugin_id, array $plugin_definition, EntityTypeManagerInterface $entity_type_manager, ElasticsearchIndexManager $elasticsearch_index_manager) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);

    $this->entity_type_manager = $entity_type_manager;
    $this->elasticsearch_index_manager = $elasticsearch_index_manager;
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
      $container->get('plugin.manager.elasticsearch_index.processor')
    );
  }

  public function processItem($data) {

    $entity_type = $data['entity_type'];
    $entity_id = $data['entity_id'];

    $entity = $this->entity_type_manager->getStorage($entity_type)->load($entity_id);

    if ($entity) {
      $this->elasticsearch_index_manager->indexEntity($entity);
    }
  }
}