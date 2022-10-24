<?php

namespace Drupal\Tests\elasticsearch_helper\Kernel;

use Drupal\elasticsearch_helper\ElasticsearchHelperQueue;
use Drupal\KernelTests\KernelTestBase;

/**
 * Tests Elasticsearch unique queue items.
 *
 * @group elasticsearch_helper
 */
class UniqueQueueTest extends KernelTestBase {

  /**
   * The modules to load to run the test.
   *
   * @var array
   */
  protected static $modules = [
    'system',
    'serialization',
    'elasticsearch_helper',
    'elasticsearch_helper_test',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->installConfig(['elasticsearch_helper']);
  }

  /**
   * Test Elasticsearch unique queue.
   */
  public function testQueueIsUnique() {
    $queue_factory = $this->container->get('elasticsearch_helper.queue_factory');

    /* @var \Drupal\Core\Queue\QueueInterface $queue */
    $queue = $queue_factory->get('queue');

    $this->assertInstanceOf(ElasticsearchHelperQueue::class, $queue);

    $data = [
      'entity_type' => 'page',
      'entity_id' => 1,
    ];

    // Add an item to the empty unique queue.
    $item_id = $queue->createItem($data);
    $this->assertNotFalse($item_id);
    $this->assertEquals(1, $queue->numberOfItems());

    // When we try to add the item again we should get an item id as the
    // item has been merged and the number of items on the queue should
    // stay the same.
    $duplicate_id = $queue->createItem($data);
    $this->assertNotFalse($duplicate_id);
    $this->assertEquals(1, $queue->numberOfItems());

    // Claim and delete the item from the queue simulating an item being
    // processed.
    $item = $queue->claimItem();
    $queue->deleteItem($item);

    // Add unique items to queue.
    foreach (range(0, 4) as $id) {
      $data = [
        'entity_type' => 'page',
        'entity_id' => $id,
      ];
      $queue->createItem($data);
    }
    $this->assertEquals(5, $queue->numberOfItems());

  }

}
