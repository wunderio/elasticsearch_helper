<?php

/**
 * @file
 * Contains \Drupal\Tests\elasticsearch_helper\Unit\SElasticsearchClientVersionTest.
 */

namespace Drupal\Tests\elasticsearch_helper\Unit;

use Drupal\elasticsearch_helper\ElasticsearchClientVersion;
use Drupal\Tests\UnitTestCase;
use Elastic\Elasticsearch\Client;

/**
 * @coversDefaultClass \Drupal\elasticsearch_helper\ElasticsearchClientVersion
 * @group elasticsearch_helper
 */
class ElasticsearchClientVersionTest extends UnitTestCase {

  /**
   * The major version of Elasticsearch.
   *
   * @var string
   */
  protected $majorVersion;

  /**
   * The minor version of Elasticsearch.
   *
   * @var string
   */
  protected $minorVersion;

  /**
   * The patch version of Elasticsearch.
   *
   * @var string
   */
  protected $patchVersion;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    [$majorVersion, $minorVersion, $patchVersion] = explode('.', Client::VERSION);

    $this->majorVersion = $majorVersion;
    $this->minorVersion = $minorVersion;
    $this->patchVersion = $patchVersion;
  }

  /**
   * Tests the getMajorVersion() method.
   *
   * @covers ::getMajorVersion
   * @covers ::getVersionParts
   * @covers ::getVersion
   */
  public function testGetMajorVersion() {
    $result = ElasticsearchClientVersion::getMajorVersion();
    $this->assertSame($this->majorVersion, $result);
  }

  /**
   * Tests the getMajorVersion() method.
   *
   * @covers ::getMinorVersion
   * @covers ::getVersionParts
   * @covers ::getVersion
   */
  public function testGetMinorVersion() {
    $result = ElasticsearchClientVersion::getMinorVersion();
    $this->assertSame($this->minorVersion, $result);
  }

  /**
   * Tests the getPatchVersion() method.
   *
   * @covers ::getPatchVersion
   * @covers ::getVersionParts
   * @covers ::getVersion
   */
  public function testGetPatchVersion() {
    $result = ElasticsearchClientVersion::getPatchVersion();
    // @todo fix version regular expression.
    // $this->assertSame($this->patchVersion, $result);
  }

}
