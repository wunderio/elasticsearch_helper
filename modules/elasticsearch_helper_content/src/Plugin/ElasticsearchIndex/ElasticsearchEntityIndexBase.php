<?php
namespace Drupal\elasticsearch_helper_content\Plugin\ElasticsearchIndex;

use Drupal\elasticsearch_helper\ElasticsearchLanguageAnalyzer;
use Drupal\elasticsearch_helper\Plugin\ElasticsearchIndexBase;

/**
 * @ElasticsearchIndex(
 *   id = "content_index",
 *   deriver = "Drupal\elasticsearch_helper_content\Plugin\Deriver\ContentIndexDeriver",
 *   supported_entity_index_classes = {
 *     "content" = "\Drupal\elasticsearch_helper_content\Plugin\ElasticsearchIndex\ElasticsearchEntityContentIndex",
 *     "field" = "\Drupal\elasticsearch_helper_content\Plugin\ElasticsearchIndex\ElasticsearchEntityFieldIndex",
 *   }
 * )
 */
abstract class ElasticsearchEntityIndexBase extends ElasticsearchIndexBase {

  /**
   * {@inheritdoc}
   */
  public function setup() {
    $index_name = $this->pluginDefinition['indexName'];

    // Only setup index if it's not already existing.
    if (!$this->client->indices()->exists(['index' => $index_name])) {
      $this->client->indices()->create([
        'index' => $index_name,
        'body' => [
          // Use a single shard to improve relevance on a small dataset.
          // TODO Make this configurable via settings.
          'number_of_shards' => 1,
          // No need for replicas, we only have one ES node.
          // TODO Make this configurable via settings.
          'number_of_replicas' => 0,
        ],
      ]);

      // Get default set of elasticsearch analyzers for the language.
      $analyzer = ElasticsearchLanguageAnalyzer::get(NULL);

      // Assemble field mapping for index.
      $mapping_context = [
        'language' => NULL,
        'analyzer' => $analyzer,
      ];
      $mapping = $this->provideMapping($mapping_context);

      // Save index mapping.
      $this->client->indices()->putMapping($mapping);
    }
  }

  /**
   * Return mapping.
   *
   * @param array $mapping_context
   *
   * @return array
   */
  protected function provideMapping(array $mapping_context) {
    return [];
  }

  /**
   * Returns stored index configuration.
   *
   * @see \Drupal\elasticsearch_helper_content\Plugin\Deriver\ContentIndexDeriver::getDerivativeDefinitions()
   *
   * @return array
   */
  protected function getIndexConfiguration() {
    return $this->pluginDefinition['configuration'];
  }

}
