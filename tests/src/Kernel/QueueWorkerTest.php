<?php

namespace Drupal\Tests\elasticsearch_helper\Kernel;

use Drupal\elasticsearch_helper\ElasticsearchHost;
use Drupal\KernelTests\KernelTestBase;
use Drupal\node\Entity\Node;
use Drupal\node\Entity\NodeType;

/**
 * Tests index entities in Elasticsearch using a queue.
 *
 * @group elasticsearch_helper
 */
class QueueWorkerTest extends KernelTestBase {

  /**
   * The modules to load to run the test.
   *
   * @var array
   */
  public static $modules = [
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
  protected function setUp() {
    parent::setUp();
    $this->installEntitySchema('user');
    $this->installEntitySchema('node');
    $this->installConfig(['elasticsearch_helper']);
    $this->installSchema('node', 'node_access');

    // Create the node bundles required for testing.
    $content_type = NodeType::create([
      'type' => 'page',
      'name' => 'page',
    ]);
    $content_type->save();
  }

  /**
   * Test queue worker functionality.
   */
  public function testDeferIndexing() {
    // Enable defer indexing.
    \Drupal::configFactory()
      ->getEditable('elasticsearch_helper.settings')
      ->set('defer_indexing', 1)
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
    $this->assertEqual($node1->id(), $item->data['entity_id'], 'Item in the queue is not same as created node entity');
    $queue_worker->processItem($item->data);
    $queue->deleteItem($item);

    $item = $queue->claimItem();
    $this->assertEqual($node2->id(), $item->data['entity_id'], 'Item in the queue is not same as created node entity');
    $queue_worker->processItem($item->data);
    $queue->deleteItem($item);

    // Wait for elasticsearch indexing to complete.
    sleep(1);

    // Check number of items in the queue.
    $this->assertEquals(0, $queue->numberOfItems());

    // Check elasticsearch index count.
    $this->assertEquals(2, $this->queryIndexCount());
  }

  /**
   * Query the index count.
   *
   * @return int
   *   The index count.
   */
  protected function queryIndexCount() {
    $host = $this
      ->config('elasticsearch_helper.settings')
      ->get('hosts')[0];
    $host = ElasticsearchHost::createFromArray($host);

    // Query URI for fetching the document from elasticsearch.
    $uri = 'http://' . $host->getHost() . ':9200/simple/_count';

    // Query index total count.
    // Use Curl for now because http client middleware fails in KernelTests
    // (See: https://www.drupal.org/project/drupal/issues/2571475)
    $curl = curl_init();
    curl_setopt($curl, CURLOPT_URL, $uri);
    curl_setopt($curl, CURLOPT_HTTPGET, TRUE);
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
    $json = curl_exec($curl);

    $response = json_decode($json, TRUE);

    return $response['count'];
  }

}
