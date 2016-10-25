<?php

namespace Drupal\elasticsearch_helper_example\Plugin\ElasticsearchIndex;

use Drupal\elasticsearch_helper\Annotation\ElasticsearchIndex;
use Drupal\elasticsearch_helper\Plugin\ElasticsearchIndexBase;
use Drupal\node\Entity\Node;

/**
 * @ElasticsearchIndex(
 *   id = "time_based_index",
 *   label = @Translation("Example Time-based Index"),
 *   indexName = "time-based-{year}{month}",
 *   typeName = "node",
 *   entityType = "node"
 * )
 */
class TimeBasedIndex extends ElasticsearchIndexBase {

  /**
   * @inheritdoc
   */
  public function serialize($source, $context = Array()) {
    /** @var Node $source */

    $data = parent::serialize($source);

    // Add the creation date.
    $data['created'] = $source->getCreatedTime();

    // Add attributes matching the placeholders in the indexName.
    $data['year'] = date('Y', $source->getCreatedTime());
    $data['month'] = date('m', $source->getCreatedTime());

    return $data;
  }
  
  /**
   * @inheritdoc
   */
  public function setup() {
    $this->client->indices()->putTemplate([
      'name' => $this->pluginId,
      'body' => [
        // Any index matching the pattern will get the given index configuration.
        'template' => $this->indexNamePattern(),
        'mappings' => [
          'node' => [
            'properties' => [
              'created' => [
                'type' => 'date',
                'format' => 'epoch_second',
              ],
              // Don't save year and month, we just need them for the placeholders.
              'year' => [
                'enabled' => FALSE,
              ],
              'month' => [
                'enabled' => FALSE,
              ],
            ]
          ]
        ]
      ]
    ]);
  }
  
}
