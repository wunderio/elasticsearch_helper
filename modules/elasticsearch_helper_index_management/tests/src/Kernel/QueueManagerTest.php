<?php

namespace Drupal\Tests\elasticsearch_helper_index_management\Kernel;

use Drupal\KernelTests\KernelTestBase;

/**
 * Tests the Queue Manager Service.
 *
 * @group elasticsearch_helper_index_manager
 */
class QueueManagerTest extends KernelTestBase {

  /**
   * Batch manager service.
   *
   * @var Drupal\elasticsearch_helper_index_management\ElasticsearchBatchManager
   */
  protected $batchManager;

  /**
   * Test dependency modules.
   *
   * @var string[]
   */
  public static $modules = [
    'system',
    'elasticsearch_helper',
    'elasticsearch_helper_index_management',
    'elasticsearch_helper_index_management_test',
  ];

  /**
   * Setup the tests.
   */
  public function setUp() {
    parent::setUp();

    $this->installSchema('elasticsearch_helper_index_management', ['es_reindex_queue']);
    $this->installConfig(['elasticsearch_helper_index_management']);

    $this->batchManager = \Drupal::service('elasticsearch_helper_index_management.queue_manager');

    $query = \Drupal::service('database')->insert('es_reindex_queue');
    $query->fields([
      'plugin_id' => 'simple_test_node_index',
      'entity_type' => 'node',
      'entity_id' => 1,
    ]);
    $query->execute();
  }

  /**
   * Test getStatus() method.
   */
  public function testStatus() {
    $this->assertEqual(
      $this->batchManager->getStatus('simple_test_node_index'),
      [
        'total' => 1,
        'processed' => 0,
        'errors' => 0,
      ]
    );
  }

  /**
   * Test setStatus() method.
   */
  public function testSetStatus() {
    $this->batchManager->setStatus(1, 1);

    $this->assertEqual(
      $this->batchManager->getStatus('simple_test_node_index'),
      [
        'total' => 1,
        'processed' => 1,
        'errors' => 0,
      ]
    );
  }

  /**
   * Test setError() method.
   */
  public function testSetError() {
    $this->batchManager->setError(1);

    $this->assertEqual(
      $this->batchManager->getStatus('simple_test_node_index'),
      [
        'total' => 1,
        'processed' => 0,
        'errors' => 1,
      ]
    );

    $this->assertEqual(1, $this->batchManager->getTotalErrors('simple_test_node_index'));
  }

  /**
   * Test getItem() method.
   */
  public function testGetItem() {
    $item = $this->batchManager->getItem(1);

    $this->assertEqual($item->entity_type, 'node');
    $this->assertEqual($item->entity_id, 1);
  }

  /**
   * Test getItems() method.
   */
  public function testGetItems() {
    $items = $this->batchManager->getItems('simple_test_node_index');

    $this->assertTrue(count($items) == 1);
  }

}
