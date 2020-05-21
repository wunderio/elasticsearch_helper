<?php

namespace Drupal\elasticsearch_helper_example\Plugin\ElasticsearchIndex;

use Drupal\elasticsearch_helper\Plugin\ElasticsearchIndexBase;

/**
 * Example versioned index.
 *
 * @ElasticsearchIndex(
 *   id = "versioned_example_index",
 *   label = @Translation("Example Versioned Index"),
 *   indexName = "versioned_example{version}",
 *   entityType = "node",
 *   versioned = TRUE
 * )
 */
class VersionedIndex extends ElasticsearchIndexBase {

  /*
   * This index requires elasticsearch_helper_index_alias to be enabled.
   */

  /**
   * {@inheritdoc}
   */
  public function serialize($source, $context = []) {
    /** @var \Drupal\node\Entity\Node $source */

    $data = parent::serialize($source);

    // Set version information.
    $data['version'] = \Drupal::service('elasticsearch_helper_index_alias.service')->getCurrentVersion();

    return $data;
  }

  /**
   * {@inheritdoc}
   */
  public function setup() {
    $version = \Drupal::service('elasticsearch_helper_index_alias.service')->getCurrentVersion();

    $index_name = $this->getIndexName(['version' => $version]);

    if (!$this->client->indices()->exists(['index' => $index_name])) {
      $this->client->indices()->create([
        'index' => $index_name,
        'body' => [
          'settings' => [
            'number_of_shards' => 1,
            'number_of_replicas' => 1,
          ],
        ],
      ]);

      $this->client->indices()->putMapping([
        'index' => $index_name,
        'body' => [
          'properties' => [
            'title' => [
              'type' => 'text',
            ],
          ],
        ],
      ]);
    }
  }

}
