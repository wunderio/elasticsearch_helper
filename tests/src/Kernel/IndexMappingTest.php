<?php

namespace Drupal\Tests\elasticsearch_helper\Kernel;

use Drupal\elasticsearch_helper\Elasticsearch\Index\FieldDefinition;
use Drupal\elasticsearch_helper\Elasticsearch\Index\MappingDefinition;
use Drupal\KernelTests\Core\Entity\EntityKernelTestBase;

/**
 * Tests elasticsearch_helper mapping functionality.
 *
 * @group elasticsearch_helper
 */
class IndexMappingTest extends EntityKernelTestBase {

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

    // Set Elasticsearch Helper configuration.
    $this->setElasticsearchHelperConfiguration();

    // Recreate indices.
    $this->removeIndices();
    $this->createIndices();

    sleep(1);
  }

  /**
   * Test index mapping.
   */
  public function testIndexMapping() {
    $index_name = $this->getSimpleNodeIndexName();

    // Query URI for fetching the document from elasticsearch.
    $uri = sprintf('%s/_mapping', $index_name);

    // Get existing mapping.
    $response = $this->httpRequest($uri);

    // Get properties.
    $properties = $response[$index_name]['mappings']['properties'];

    $this->assertEquals('integer', $properties['id']['type'], 'ID field is found');
    $this->assertEquals('keyword', $properties['uuid']['type'], 'UUID field is found');
    $this->assertEquals('text', $properties['title']['type'], 'Title field is found');
    $this->assertEquals('boolean', $properties['status']['type'], 'Status field is found');
    $this->assertEquals('keyword', $properties['extra']['type'], 'Extra field is found');
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

    $this->assertEquals($expected, $mapping_definition->toArray());
  }

}
