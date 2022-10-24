<?php

namespace Drupal\Tests\elasticsearch_helper\Kernel;

use Drupal\elasticsearch_helper\Elasticsearch\Index\FieldDefinition;
use Drupal\elasticsearch_helper\Elasticsearch\Index\MappingDefinition;
use Drupal\elasticsearch_helper\ElasticsearchClientVersion;
use Drupal\elasticsearch_helper\ElasticsearchHost;
use Drupal\KernelTests\Core\Entity\EntityKernelTestBase;

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
  protected static $modules = [
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
  protected function setUp(): void {
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
    $host = $this
      ->config('elasticsearch_helper.settings')
      ->get('hosts')[0];
    $host = ElasticsearchHost::createFromArray($host);

    $index_name = 'node_index';

    // Query URI for fetching the document from elasticsearch.
    $uri = 'http://' . $host->getHost() . ':9200/' . $index_name . '/_mapping';

    $response = $this->httpRequest($uri);

    if (ElasticsearchClientVersion::getMajorVersion() >= 7) {
      // ES7 mapping structure with no type name.
      $properties = $response[$index_name]['mappings']['properties'];
    }
    else {
      // ES6 mapping structure with type name.
      $properties = $response[$index_name]['mappings']['node']['properties'];
    }

    $this->assertEquals($properties['id']['type'], 'integer', 'ID field is found');
    $this->assertEquals($properties['status']['type'], 'boolean', 'Status field is found');
    $this->assertEquals($properties['title']['type'], 'text', 'Title field is found');
    $this->assertEquals($properties['uuid']['type'], 'keyword', 'UUID field is found');
  }

  /**
   * Test mapping definition.
   */
  public function testMappingDefinition() {
    $mapping_definition = MappingDefinition::create()
      ->addProperty('id', FieldDefinition::create('integer'))
      ->addProperty('uuid', FieldDefinition::create('keyword'))
      ->addProperty('title', FieldDefinition::create('text'))
      ->addProperty('status', FieldDefinition::create('boolean'));

    $expected = [
      'properties' => [
        'id' => ['type' => 'integer'],
        'uuid' => ['type' => 'keyword'],
        'title' => ['type' => 'text'],
        'status' => ['type' => 'boolean'],
      ]
    ];

    $this->assertEquals($mapping_definition->toArray(), $expected);
  }

}
