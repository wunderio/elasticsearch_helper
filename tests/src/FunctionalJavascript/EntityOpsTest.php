<?php

namespace Drupal\Tests\elasticsearch_helper\FunctionalJavascript;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\FunctionalJavascriptTests\WebDriverTestBase;

/**
 * Test basic functionality.
 *
 * @group elasticsearch_helper
 */
class EntityOpsTest extends WebDriverTestBase {

  /**
   * @var string
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['elasticsearch_helper'];

  /**
   * Test what happens when creating an entity.
   */
  public function testRegularEntityCreate() {
    $queue = \Drupal::queue('elasticsearch_helper_indexing');
    $entity = $this->createMock(ContentEntityInterface::class);
    elasticsearch_helper_entity_insert($entity);
    $this->assertEquals(0, $queue->numberOfItems());
    \Drupal::configFactory()
      ->getEditable('elasticsearch_helper.settings')
      ->set('defer_indexing', TRUE)
      ->save();
    elasticsearch_helper_entity_insert($entity);
    $this->assertEquals(1, $queue->numberOfItems());
  }

}
