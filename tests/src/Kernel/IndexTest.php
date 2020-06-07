<?php

namespace Drupal\Tests\elasticsearch_helper\Kernel;

use Drupal\KernelTests\Core\Entity\EntityKernelTestBase;
use Drupal\node\Entity\Node;
use Drupal\node\Entity\NodeType;

/**
 * Tests elasticsearch_helper index functionality.
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
    'system',
    'field',
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

    // Create the node bundles required for testing.
    $type = NodeType::create([
      'type' => 'page',
      'name' => 'page',
    ]);

    $type->save();

    try {
      $client = \Drupal::service('elasticsearch_helper.elasticsearch_client');
      $client->indices()->delete(['index' => 'simple']);
    }
    catch (\Exception $e) {
      // Do nothing.
    }
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
    $elasticsearch_host = $this
      ->config('elasticsearch_helper.settings')
      ->get('elasticsearch_helper.host');

    // Query URI for fetching the document from elasticsearch.
    $uri = 'http://' . $elasticsearch_host . ':9200/simple/_search?q=id:' . $docId;

    // Query elasticsearch.
    // Use Curl for now because http client middleware fails in KernelTests
    // (See: https://www.drupal.org/project/drupal/issues/2571475)
    $curl = curl_init();
    curl_setopt($curl, CURLOPT_URL, $uri);
    curl_setopt($curl, CURLOPT_HTTPGET, TRUE);
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
    $json = curl_exec($curl);

    $response = json_decode($json, TRUE);

    return $response;
  }

  /**
   * Test node indexing.
   */
  public function testNodeIndexing() {
    $elasticsearch_host = $this
      ->config('elasticsearch_helper.settings')
      ->get('elasticsearch_helper.host');

    // Create a test page to be indexed.
    $page = Node::create([
      'type' => 'page',
      'title' => $this->randomMachineName(),
      'uid' => 1,
    ]);

    // Entity save will index the page.
    $page->save();

    // Wait for elasticsearch indexing to complete.
    sleep(1);

    // Query URI for fetching the document from elasticsearch.
    $uri = 'http://' . $elasticsearch_host . ':9200/simple/_search?q=id:' . $page->id();

    // Query elasticsearch.
    // Use Curl for now because http client middleware fails in KernelTests
    // (See: https://www.drupal.org/project/drupal/issues/2571475)
    $curl = curl_init();
    curl_setopt($curl, CURLOPT_URL, $uri);
    curl_setopt($curl, CURLOPT_HTTPGET, TRUE);
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
    $json = curl_exec($curl);

    $response = json_decode($json, TRUE);

    $this->assertEqual($response['hits']['hits'][0]['_source']['title'], $page->getTitle(), 'Title field is found in document');
    $this->assertEqual($response['hits']['hits'][0]['_source']['status'], TRUE, 'Status field is found in document');
  }

}
