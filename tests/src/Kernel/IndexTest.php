<?php

namespace Drupal\Tests\elasticsearch_helper\Kernel;

use Drupal\elasticsearch_helper\ElasticsearchClientVersion;
use Drupal\elasticsearch_helper\ElasticsearchHost;
use Drupal\KernelTests\Core\Entity\EntityKernelTestBase;
use Drupal\node\Entity\Node;
use Drupal\node\Entity\NodeType;

/**
 * Tests elasticsearch_helper indexing functionality (insert, update, delete)
 *
 * @group elasticsearch_helper
 */
class IndexTest extends EntityKernelTestBase {

  /**
   * The modules to load to run the test.
   *
   * @var array
   */
  public static $modules = [
    'node',
    'user',
    'system',
    'field',
    'text',
    'filter',
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
    $this->installSchema('node', 'node_access');

    // Delete any pre-existing indices.
    // @TODO, Setup mapping.
    try {
      $client = \Drupal::service('elasticsearch_helper.elasticsearch_client');
      $client->indices()->delete(['index' => 'simple']);
    }
    catch (\Exception $e) {
      // Do nothing.
    }

    // Create the node bundles required for testing.
    $type = NodeType::create([
      'type' => 'page',
      'name' => 'page',
    ]);

    $type->save();

    // Create a test page to be indexed.
    $this->node = Node::create([
      'type' => 'page',
      'title' => $this->randomMachineName(),
      'uid' => 1,
    ]);

    // Entity save will index the page.
    $this->node->save();
  }

  /**
   * HTTP request with curl.
   *
   * @param string $uri
   *   The request uri
   *
   * @return array
   *   The decoded response.
   */
  protected function httpRequest($uri) {
    // Query elasticsearch.
    // Use Curl for now because http client middleware fails in KernelTests
    // (See: https://www.drupal.org/project/drupal/issues/2571475)
    $curl = curl_init();
    curl_setopt($curl, CURLOPT_URL, $uri);
    curl_setopt($curl, CURLOPT_HTTPGET, TRUE);
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
    $json = curl_exec($curl);

    return json_decode($json, TRUE);
  }


  /**
   * Query the test index.
   *
   * @param int $docId
   *   The document id to query.
   *
   * @return array
   *   The response.
   */
  protected function queryIndex($docId) {
    $host = $this
      ->config('elasticsearch_helper.settings')
      ->get('hosts')[0];
    $host = ElasticsearchHost::createFromArray($host);

    // Query URI for fetching the document from elasticsearch.
    $uri = 'http://' . $host->getHost() . ':9200/simple/_search?q=id:' . $docId;
    return $this->httpRequest($uri);
  }

  /**
   * Test node insert.
   */
  public function testNodeInsert() {
    // Wait for elasticsearch indexing to complete.
    sleep(1);

    $response = $this->queryIndex($this->node->id());

    $this->assertEqual($response['hits']['hits'][0]['_source']['title'], $this->node->getTitle(), 'Title field is found in document');
    $this->assertEqual($response['hits']['hits'][0]['_source']['status'], TRUE, 'Status field is found in document');
  }

  /**
   * Test node update.
   */
  public function testNodeUpdate() {
    // Entity save will index the page.
    $this->node->save();

    // Wait for elasticsearch indexing to complete.
    sleep(1);

    $response = $this->queryIndex($this->node->id());

    $this->assertEqual($response['hits']['hits'][0]['_source']['title'], $this->node->getTitle(), 'Title field is found in document');

    // Update the node title.
    $new_title = $this->randomMachineName();
    $this->node->setTitle($new_title);
    $this->node->save();

    // Wait for elasticsearch indexing to complete.
    sleep(1);

    $response = $this->queryIndex($this->node->id());

    $this->assertEqual($response['hits']['hits'][0]['_source']['title'], $new_title, 'Title field is found in document');
  }

  /**
   * Test node delete.
   */
  public function testNodeDelete() {
    // Entity save will index the page.
    $this->node->save();

    // Wait for elasticsearch indexing to complete.
    sleep(1);

    $response = $this->queryIndex($this->node->id());

    $this->assertEqual($response['hits']['hits'][0]['_source']['title'], $this->node->getTitle(), 'Title field is found in document');

    // Delete node.
    $this->node->delete();

    // Wait for elasticsearch indexing to complete.
    sleep(1);

    $response = $this->queryIndex($this->node->id());

    $this->assertEmpty($response['hits']['hits'], 'Document not found');
  }

}
