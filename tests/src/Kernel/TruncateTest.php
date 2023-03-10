<?php

namespace Drupal\Tests\elasticsearch_helper\Kernel;

use Drupal\KernelTests\Core\Entity\EntityKernelTestBase;
use Drupal\language\Entity\ConfigurableLanguage;
use Drupal\node\Entity\Node;
use Drupal\node\Entity\NodeType;

/**
 * Tests elasticsearch_helper truncating functionality.
 *
 * @group elasticsearch_helper
 */
class TruncateTest extends EntityKernelTestBase {

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

    sleep(1);
  }

  /**
   * Count the number of documents in the index.
   *
   * @param string $index_name
   *   The index to query.
   *
   * @return array
   *   The response.
   */
  protected function countIndex($index_name) {
    $uri = sprintf('%s/_count', $index_name);

    return $this->httpRequest($uri);
  }

  /**
   * Test index truncate.
   */
  public function testIndexTruncate() {
    // Get document count in the index.
    $response = $this->countIndex($this->getSimpleNodeIndexName());

    $this->assertEquals(1, $response['count'], 'Document count in the index is equal to node count.');

    // Get index plugin for simple node index.
    /** @var \Drupal\elasticsearch_helper\Plugin\ElasticsearchIndexInterface $index_plugin */
    $index_plugin = \Drupal::service('plugin.manager.elasticsearch_index.processor')->createInstance('test_simple_node_index');

    // Truncate the index.
    $index_plugin->truncate();

    sleep(1);

    // Get document count in the index.
    $response = $this->countIndex($this->getSimpleNodeIndexName());

    $this->assertEquals(0, $response['count'], 'Document count in the index is equal to 0.');
  }

}
