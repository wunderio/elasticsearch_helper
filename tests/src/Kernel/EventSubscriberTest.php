<?php

namespace Drupal\Tests\elasticsearch_helper\Kernel;

use Drupal\KernelTests\Core\Entity\EntityKernelTestBase;
use Drupal\language\Entity\ConfigurableLanguage;
use Drupal\node\Entity\Node;
use Drupal\node\Entity\NodeType;

/**
 * Tests elasticsearch_helper event subscribers.
 *
 * @group elasticsearch_helper
 */
class EventSubscriberTest extends EntityKernelTestBase {

  use IndexOperationTrait;

  /**
   * @var \Drupal\elasticsearch_helper\Plugin\ElasticsearchIndexManager
   */
  protected $index_plugin_manager;

  /**
   * Node entity.
   *
   * @var \Drupal\node\NodeInterface
   */
  protected $node;

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
    'language',
    'content_translation',
    'elasticsearch_helper',
    'elasticsearch_helper_test',
    'elasticsearch_helper_test_subscriber',
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

    $this->index_plugin_manager = \Drupal::service('plugin.manager.elasticsearch_index.processor');

    // Add new language.
    ConfigurableLanguage::createFromLangcode('lv')->save();

    // Create the node bundles required for testing.
    $type = NodeType::create([
      'type' => 'page',
      'name' => 'Page',
    ]);

    $type->save();

    // Create a test page to be indexed.
    $node_title = $this->randomMachineName();

    // Create node.
    $this->node = Node::create([
      'type' => 'page',
      'title' => $node_title,
      'uid' => 1,
    ]);

    // Entity save will index the page.
    $this->node->save();

    $this->state->deleteMultiple([
      'created_indexes',
      'document_index',
      'document_get',
      'document_upsert',
      'document_delete',
      'query_search',
    ]);

    sleep(1);
  }

  /**
   * Query the test index.
   *
   * @param int $document_id
   *   The document id to query.
   * @param string $index_name
   *   The index to query.
   *
   * @return array
   *   The response.
   */
  protected function queryIndex($document_id, $index_name) {
    // Query URI for fetching the document from elasticsearch.
    $uri = sprintf('%s/_search?q=id:%s', $index_name, $document_id);

    return $this->httpRequest($uri);
  }

  /**
   * Test index create.
   */
  public function testIndexCreate() {
    $this->removeIndices();
    $this->index_plugin_manager->createInstance('test_multilingual_node_index')->setup();
    sleep(1);
    $indexes = $this->container->get('state')->get('created_indexes');
    $this->assertContains('test-multilingual-node-index-en', $indexes);
  }

  /**
   * Test node insert.
   */
  public function testDocumentIndex() {
    $this->index_plugin_manager->indexEntity($this->node);
    sleep(1);
    $this->assertEquals($this->node->id(), $this->container->get('state')->get('document_index'), 'Document index event triggered successfully');
  }

  /**
   * Test node insert.
   */
  public function testDocumentGet() {
    $response = $this->queryIndex($this->node->id(), $this->getMultilingualNodeIndexName('en'));
    $this->assertEquals($this->node->getTitle(), $response['hits']['hits'][0]['_source']['title'], 'Node found in index');
    $this->index_plugin_manager->createInstance('test_multilingual_node_index')->get($this->node);
    $this->assertEquals($this->node->getTitle(), $this->container->get('state')->get('document_get'), 'Document get event triggered successfully');
  }

  /**
   * Test node update.
   */
  public function testDocumentUpsert() {
    $response = $this->queryIndex($this->node->id(), $this->getMultilingualNodeIndexName('en'));
    $this->assertEquals($this->node->getTitle(), $response['hits']['hits'][0]['_source']['title'], 'Node found in index');
    $this->index_plugin_manager->createInstance('test_multilingual_node_index')->upsert($this->node);
    // Wait for Elasticsearch indexing to complete.
    sleep(1);
    $this->assertEquals($this->node->getTitle(), $this->container->get('state')->get('document_upsert'), 'Document upsert event triggered successfully');
  }

  /**
   * Test node delete.
   */
  public function testDocumentDelete() {
    $response = $this->queryIndex($this->node->id(), $this->getMultilingualNodeIndexName('en'));
    $this->assertEquals($this->node->getTitle(), $response['hits']['hits'][0]['_source']['title'], 'Node found in index');
    // Delete node.
    $this->node->delete();
    // Wait for Elasticsearch indexing to complete.
    sleep(1);
    $this->assertEquals($this->node->id(), $this->container->get('state')->get('document_delete'), 'Document delete event triggered successfully');
    $response = $this->queryIndex($this->node->id(), $this->getMultilingualNodeIndexName('en'));
    $this->assertEmpty($response['hits']['hits'], 'Document not found');
  }

  /**
   * Test query search.
   */
  public function testQuerySearch() {
    $params = [
      'index' => $this->getMultilingualNodeIndexName('en'),
    ];
    $response = $this->index_plugin_manager->createInstance('test_multilingual_node_index')->search($params);
    $total = 0;
    if (isset($response['hits'], $response['hits']['total'], $response['hits']['total']['value'])) {
      $total = $response['hits']['total']['value'];
    }
    $this->assertEquals($this->container->get('state')->get('query_search'), $total, 'Total documents in index');
  }

}
