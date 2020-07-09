<?php

namespace Drupal\Tests\elasticsearch_helper\Kernel;

use Drupal\elasticsearch_helper\ElasticsearchClientVersion;
use Drupal\KernelTests\Core\Entity\EntityKernelTestBase;
use Drupal\node\Entity\Node;
use Drupal\node\Entity\NodeType;

/**
 * Tests elasticsearch_helper mapping functionality.
 *
 * @group elasticsearch_helper
 */
class IndexMappingTest extends EntityKernelTestBase {

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
   * Test index mapping.
   */
  public function testIndexMapping() {
    $elasticsearch_host = $this
      ->config('elasticsearch_helper.settings')
      ->get('elasticsearch_helper.host');

    $index_name = 'node_index';

    // Query URI for fetching the document from elasticsearch.
    $uri = 'http://' . $elasticsearch_host . ':9200/'. $index_name .'/_mapping';

    $response = $this->httpRequest($uri);

    if (ElasticsearchClientVersion::getMajorVersion() >= 7) {
      // ES7 mapping structure with no type name.
      $properties = $response[$index_name]['mappings']['properties'];
    } else {
      // ES6 mapping structure with type name.
      $properties = $response[$index_name]['mappings']['node']['properties'];
    }

    $this->assertEqual($properties['id']['type'], 'integer', 'ID field is found');
    $this->assertEqual($properties['status']['type'], 'boolean', 'Status field is found');
    $this->assertEqual($properties['title']['type'], 'text', 'Title field is found');
    $this->assertEqual(
      $properties['title']['fields'],
      ['keyword' => ['type' => 'keyword', 'ignore_above' => 256]],
      'Title sub-field is found'
    );
    $this->assertEqual($properties['uuid']['type'], 'text', 'UUID field is found');
  }

}
