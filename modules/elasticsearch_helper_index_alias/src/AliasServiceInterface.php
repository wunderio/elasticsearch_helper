<?php

namespace Drupal\elasticsearch_helper_index_alias;

use Elasticsearch\Client;

/**
 * Interface AliasServiceInterface.
 */
interface AliasServiceInterface {

  /**
   * Update all versioned index.
   */
  public function updateAll();

  /**
   * Get current version.
   *
   * @return string
   *   The string version.
   */
  public function getCurrentVersion(): string;

  /**
   * Increments the version.
   */
  public function incrementVersion();

  /**
   * Get defintions of versioned indices.
   *
   * @return array
   *   An array of index plugin definitions.
   */
  public function getVersionedIndexDefinitions() : array;

  /**
   * Get only the index names of versioned index.
   *
   * @return array
   *   An array of index names.
   */
  public function getVersionedIndexes() : array;

  /**
   * Point the alias to the new index version name.
   *
   * Old indices will be deleted during first use.
   *
   * @param \Elasticsearch\Client $client
   *   The elasticsearch client (Optional)
   * @param string $index_name
   *   The index name.
   * @param string $version
   *   The index version.
   *
   * @return bool
   *   Returns True or False.
   */
  public function updateIndexAlias(Client $client, $index_name, $version) : bool;

}
