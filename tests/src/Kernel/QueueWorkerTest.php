<?php

namespace Drupal\Tests\elasticsearch_helper\Kernel;

use Drupal\KernelTests\KernelTestBase;
use Drupal\node\Entity\Node;
use Drupal\node\Entity\NodeType;

/**
 * Tests index entities in Elasticsearch using a queue.
 *
 * @group elasticsearch_helper
 */
class QueueWorkerTest extends KernelTestBase {

  use IndexOperationTrait;

  /**
   * The modules to load to run the test.
   *
   * @var array
   */
  protected static $modules = [
    'node',
    'user',
    'system',
    'serialization',
    'elasticsearch_helper',
    'elasticsearch_helper_test',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->installEntitySchema('user');
    $this->installEntitySchema('node');
    $this->installConfig(['elasticsearch_helper']);
    $this->installSchema('node', 'node_access');

    // Set Elasticsearch Helper configuration.
    $this->setElasticsearchHelperConfiguration();

    // Remove testing Elasticsearch indices.
    $this->removeIndices();

    // Create the node bundles required for testing.
    $content_type = NodeType::create([
      'type' => 'page',
      'name' => 'Page',
    ]);

    $content_type->save();

    sleep(1);
  }

  /**
   * Test queue worker functionality.
   */
  public function testDeferIndexing() {
    // Enable defer indexing.
    \Drupal::configFactory()
      ->getEditable('elasticsearch_helper.settings')
      ->set('defer_indexing', TRUE)
      ->save();

    // Return elasticsearch_helper related queue and queue worker.
    $queue_name = 'elasticsearch_helper_indexing';
    $queue = \Drupal::queue($queue_name);
    $queue_worker = \Drupal::service('plugin.manager.queue_worker')->createInstance($queue_name);

    // Add test pages to queue.
    $node1 = Node::create([
      'type' => 'page',
      'title' => $this->randomMachineName(),
    ]);
    $node1->save();

    $node2 = Node::create([
      'type' => 'page',
      'title' => $this->randomMachineName(),
    ]);
    $node2->save();

    // Check number of items in the queue.
    $this->assertEquals(2, $queue->numberOfItems());

    // Process the queue items and ensure that index was updated too.
    $item = $queue->claimItem();
    $this->assertEquals($node1->id(), $item->data['entity_id'], 'Item in the queue is not same as created node entity');
    $queue_worker->processItem($item->data);
    $queue->deleteItem($item);

    $item = $queue->claimItem();
    $this->assertEquals($node2->id(), $item->data['entity_id'], 'Item in the queue is not same as created node entity');
    $queue_worker->processItem($item->data);
    $queue->deleteItem($item);

    // Wait for Elasticsearch indexing to complete.
    sleep(1);

    // Check number of items in the queue.
    $this->assertEquals(0, $queue->numberOfItems());

    // Check elasticsearch index count.
    $this->assertEquals(2, $this->queryIndexCount());
  }

  /**
   * Query the index document count.
   *
   * @return int
   *   The index document count.
   */
  protected function queryIndexCount() {
    $index_name = $this->getSimpleNodeIndexName();

    // Query URI for fetching the document from elasticsearch.
    $uri = sprintf('%s/_count', $index_name);

    // Get existing mapping.
    $response = $this->httpRequest($uri);

    return $response['count'];
  }

}
