<?php

namespace Drupal\elasticsearch_helper;

use Elasticsearch\Client;

/**
 * Class ElasticsearchClientVersion
 */
class ElasticsearchClientVersion {

  /**
   * Returns Elasticsearch client version part array.
   *
   * @return array
   */
  public static function getVersionParts() {
    preg_match('/^(?:(\d+)\.)?(?:(\d+)\.)?(?:(\d+)\.)?/', self::getVersion(), $matches);
    // Remove the full match.
    array_shift($matches);
    // Provide default values for major, minor and patch versions.
    $matches += [NULL, NULL, NULL];

    return $matches;
  }

  /**
   * Returns full version.
   *
   * @return string
   */
  public static function getVersion() {
    return Client::VERSION;
  }

  /**
   * Returns major version.
   *
   * @return string
   */
  public static function getMajorVersion() {
    return self::getVersionParts()[0];
  }

  /**
   * Returns minor version.
   *
   * @return string
   */
  public static function getMinorVersion() {
    return self::getVersionParts()[1];
  }

  /**
   * Returns patch version.
   *
   * @return string
   */
  public static function getPatchVersion() {
    return self::getVersionParts()[2];
  }

}
