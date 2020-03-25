<?php

namespace Drupal\elasticsearch_helper_index_management;

use Drupal\Core\Database\Driver\mysql\Connection;
use Drupal\Core\Database\IntegrityConstraintViolationException;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\elasticsearch_helper\Plugin\ElasticsearchIndexManager;

/**
 * Class ElasticsearchQueueManager.
 */
class ElasticsearchQueueManager implements ElasticsearchQueueManagerInterface {

  /**
   * Drupal\Core\Database\Driver\mysql\Connection definition.
   *
   * @var \Drupal\Core\Database\Driver\mysql\Connection
   */
  protected $database;

  /**
   * Drupal\elasticsearch_helper\Plugin\ElasticsearchIndexManager definition.
   *
   * @var \Drupal\elasticsearch_helper\Plugin\ElasticsearchIndexManager
   */
  protected $pluginManagerElasticsearchIndexProcessor;

  /**
   * Drupal\Core\Entity\EntityTypeManagerInterface definition.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Database table name.
   *
   * @var string
   */
  protected $table = 'es_reindex_queue';

  /**
   * Constructs a new ElasticsearchQueueManager object.
   */
  public function __construct(Connection $database, ElasticsearchIndexManager $plugin_manager_elasticsearch_index_processor, EntityTypeManagerInterface $entity_type_manager) {
    $this->database = $database;
    $this->pluginManagerElasticsearchIndexProcessor = $plugin_manager_elasticsearch_index_processor;
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public function getStatus($index_id) {
    return [
      'total' => $this->getTotal($index_id),
      'processed' => $this->getProcessed($index_id),
      'errors' => $this->getTotalErrors($index_id),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getTotal($index_id) {
    $definition = $this->pluginManagerElasticsearchIndexProcessor->getDefinition($index_id);

    $query = $this->database->select($this->table);
    $query->condition('plugin_id', $definition['id']);
    $query->condition('entity_type', $definition['entityType']);

    return $query->countQuery()->execute()->fetchField();
  }

  /**
   * {@inheritdoc}
   */
  public function getProcessed($index_id) {
    $definition = $this->pluginManagerElasticsearchIndexProcessor->getDefinition($index_id);

    $query = $this->database->select($this->table);
    $query->condition('plugin_id', $definition['id']);
    $query->condition('entity_type', $definition['entityType']);
    $query->condition('status', 1);

    return $query->countQuery()->execute()->fetchField();
  }

  /**
   * {@inheritdoc}
   */
  public function getTotalErrors($index_id) {
    $definition = $this->pluginManagerElasticsearchIndexProcessor->getDefinition($index_id);

    $query = $this->database->select($this->table);
    $query->condition('plugin_id', $definition['id']);
    $query->condition('entity_type', $definition['entityType']);
    $query->condition('error', 1);

    return $query->countQuery()->execute()->fetchField();
  }

  /**
   * {@inheritdoc}
   */
  public function addAll($index_id) {
    $definition = $this->pluginManagerElasticsearchIndexProcessor->getDefinition($index_id);

    $query = $this
      ->entityTypeManager
      ->getStorage($definition['entityType'])
      ->getQuery();

    if (isset($definition['bundle'])) {
      $entities = $query
        ->condition('bundle', $definition['bundle'])
        ->execute();
    }
    else {
      $entities = $query->execute();
    }

    foreach ($entities as $entity_id) {
      $query = $this->database->insert($this->table);
      $query->fields([
        'plugin_id' => $definition['id'],
        'entity_type' => $definition['entityType'],
        'entity_id' => $entity_id,
      ]);

      try {
        $query->execute();
      }
      catch (IntegrityConstraintViolationException $e) {
        // Skip duplicates.
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function clear($index_id) {
    $definition = $this->pluginManagerElasticsearchIndexProcessor->getDefinition($index_id);

    $query = $this->database->delete($this->table);

    $query->condition('plugin_id', $definition['id']);
    $query->condition('entity_type', $definition['entityType']);

    $query->execute();
  }

  /**
   * {@inheritdoc}
   */
  public function getItems($index_id) {
    $definition = $this->pluginManagerElasticsearchIndexProcessor->getDefinition($index_id);

    $query = $this->database->select($this->table, 'queue');
    $query->fields('queue', ['id', 'status', 'error']);

    $query->condition('plugin_id', $definition['id']);
    $query->condition('entity_type', $definition['entityType']);

    return $query->execute()->fetchAll();
  }

  /**
   * {@inheritdoc}
   */
  public function getItem($id) {
    $query = $this->database->select($this->table, 'queue');
    $query->fields('queue', [
      'id',
      'entity_type',
      'entity_id',
      'error',
      'status',
    ]);
    $query->condition('id', $id);

    return $query->execute()->fetchObject();
  }

  /**
   * {@inheritdoc}
   */
  public function setStatus($id, $status) {
    $query = $this->database->update($this->table);
    $query->condition('id', $id);
    $query->fields(['status' => (int) $status]);
    return $query->execute();
  }

  /**
   * {@inheritdoc}
   */
  public function setError($id, $error = 1) {
    $query = $this->database->update($this->table);
    $query->condition('id', $id);
    $query->fields(['error' => (int) $error]);
    return $query->execute();
  }

}
