<?php

namespace Drupal\Tests\elasticsearch_helper\Kernel;

use Drupal\KernelTests\Core\Entity\EntityKernelTestBase;
use Drupal\language\Entity\ConfigurableLanguage;
use Drupal\node\Entity\Node;
use Drupal\node\Entity\NodeType;

/**
 * Tests elasticsearch_helper indexing functionality (insert, update, delete)
 *
 * @group elasticsearch_helper
 */
class IndexTest extends EntityKernelTestBase {

  use IndexOperationTrait;

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
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->installConfig(['elasticsearch_helper']);
    $this->installSchema('node', 'node_access');

    // Recreate indices.
    $this->removeIndices();
    $this->createIndices();

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

    // Add translation to the node.
    $this->node->addTranslation('lv', [
      'title' => $node_title . '-lv'
    ]);

    // Entity save will index the page.
    $this->node->save();

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
   * Test node insert.
   */
  public function testNodeInsert() {
    // Get main translation from Elasticsearch index.
    $response = $this->queryIndex($this->node->id(), $this->getMultilingualNodeIndexName('en'));

    $this->assertEquals($this->node->getTitle(), $response['hits']['hits'][0]['_source']['title'], 'Title field is found in the document');
    $this->assertEquals(TRUE, $response['hits']['hits'][0]['_source']['status'], 'Status field is found in the document');

    // Get translation document from Elasticsearch index.
    $response = $this->queryIndex($this->node->id(), $this->getMultilingualNodeIndexName('lv'));

    $this->assertEquals($this->node->getTranslation('lv')->getTitle(), $response['hits']['hits'][0]['_source']['title'], 'Title field is found in the translation document');
  }

  /**
   * Test node update.
   */
  public function testNodeUpdate() {
    $response = $this->queryIndex($this->node->id(), $this->getMultilingualNodeIndexName('en'));

    $this->assertEquals($this->node->getTitle(), $response['hits']['hits'][0]['_source']['title'], 'Title field is found in document');

    // Update the node title.
    $new_title = $this->randomMachineName();
    $this->node->setTitle($new_title);
    $this->node->save();

    // Wait for Elasticsearch indexing to complete.
    sleep(1);

    $response = $this->queryIndex($this->node->id(), $this->getMultilingualNodeIndexName('en'));

    $this->assertEquals($new_title, $response['hits']['hits'][0]['_source']['title'], 'Title field is found in document');
  }

  /**
   * Test node delete.
   */
  public function testNodeDelete() {
    // Entity save will index the page.
    $this->node->save();

    // Wait for Elasticsearch indexing to complete.
    sleep(1);

    $response = $this->queryIndex($this->node->id(), $this->getMultilingualNodeIndexName('en'));

    $this->assertEquals($this->node->getTitle(), $response['hits']['hits'][0]['_source']['title'], 'Title field is found in document');

    // Delete node.
    $this->node->delete();

    // Wait for Elasticsearch indexing to complete.
    sleep(1);

    $response = $this->queryIndex($this->node->id(), $this->getMultilingualNodeIndexName('en'));

    $this->assertEmpty($response['hits']['hits'], 'Document not found');
  }

}
