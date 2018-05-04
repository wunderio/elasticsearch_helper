<?php

namespace Drupal\elasticsearch_helper_content\Plugin\ElasticsearchIndex;

use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\elasticsearch_helper\ElasticsearchLanguageAnalyzer;
use Drupal\elasticsearch_helper\Plugin\ElasticsearchIndexBase;
use Elasticsearch\Client;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Serializer\Serializer;

/**
 * A multilingual content index base class.
 */
abstract class MultilingualContentIndexBase extends ElasticsearchIndexBase {

  /**
   * @var \Drupal\Core\Language\LanguageManagerInterface
   */
  protected $languageManager;

  /**
   * MultilingualContentIndexBase constructor.
   *
   * @param array $configuration
   * @param string $plugin_id
   * @param mixed $plugin_definition
   * @param \Elasticsearch\Client $client
   * @param \Symfony\Component\Serializer\Serializer $serializer
   * @param \Psr\Log\LoggerInterface $logger
   * @param \Drupal\Core\Language\LanguageManagerInterface $languageManager
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, Client $client, Serializer $serializer, LoggerInterface $logger, LanguageManagerInterface $languageManager) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $client, $serializer, $logger);

    $this->languageManager = $languageManager;
  }

  /**
   * @param \Symfony\Component\DependencyInjection\ContainerInterface $container
   * @param array $configuration
   * @param string $plugin_id
   * @param mixed $plugin_definition
   * @return static
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('elasticsearch_helper.elasticsearch_client'),
      $container->get('serializer'),
      $container->get('logger.factory')->get('elasticsearch_helper'),
      $container->get('language_manager')
    );
  }

  /**
   * @inheritdoc
   */
  public function serialize($source, $context = []) {
    /** @var \Drupal\core\Entity\ContentEntityBase $source */

    $data = parent::serialize($source, $context);

    // Add the language code to be used as a token.
    $data['langcode'] = $source->language()->getId();

    return $data;
  }

  /**
   * @inheritdoc
   */
  public function index($source) {
    /** @var \Drupal\core\Entity\ContentEntityBase $source */
    foreach ($source->getTranslationLanguages() as $langcode => $language) {
      if ($source->hasTranslation($langcode)) {
        parent::index($source->getTranslation($langcode));
      }
    }
  }

  /**
   * @inheritdoc
   */
  public function delete($source) {
    /** @var \Drupal\core\Entity\ContentEntityBase $source */
    foreach ($source->getTranslationLanguages() as $langcode => $language) {
      // @Todo How to delete a deleted translation of an entity?
      //       (->hasTranslation will be false, but the old translation will
      //        still be in the index and needs to be deleted).
      // @Todo How to delete translations of a language that gets removed?
      if ($source->hasTranslation($langcode)) {
        parent::delete($source->getTranslation($langcode));
      }
    }
  }

  /**
   * @inheritdoc
   */
  public function setup() {
    // @Todo How to add (sub-) index for newly added language?
    // @Todo How to drop (sub-) index for newly removed language?
    // @Todo How to consider content with neutral or undefined language?

    // Create one index per language, so that we can have different analyzers.
    foreach ($this->languageManager->getLanguages() as $langcode => $language) {
      // Determine index name from configured index name and language.
      $index_name = str_replace('{langcode}', $langcode, $this->pluginDefinition['indexName']);

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
        $analyzer = ElasticsearchLanguageAnalyzer::get($langcode);

        // Assemble field mapping for index.
        $mapping_context = [
          'index_name' => $index_name,
          'languge' => $language,
          'analyzer' => $analyzer,
        ];
        $mapping = $this->provideMapping($mapping_context);

        // Save index mapping.
        $this->client->indices()->putMapping($mapping);
      }
    }
  }

  /**
   * Provides the elasticsearch field mapping for this index.
   *
   * @param array $context
   *   Provides some context via keys 'index_name', 'language', 'analyzer'.
   *
   * @return array
   *   The ES mapping structure.
   */
  public function provideMapping(array $context) {
    $type_keyword = [
      'type' => 'keyword',
    ];
    $type_text = [
      'type' => 'text',
      'analyzer' => $context['analyzer'],
    ];

    $mapping = [
      'index' => $context['index_name'],
      'type' => $this->pluginDefinition['typeName'],
      'body' => [
        'properties' => [
          'id' => ['type' => 'integer'],
          'uuid' => $type_keyword,
          'entity' => $type_keyword,
          'bundle' => $type_keyword,
          'entity_label' => $type_keyword,
          'bundle_label' => $type_keyword,
          'url_internal' => $type_keyword,
          'url_alias' => $type_keyword,
          'label' => $type_text,
          'created' => [
            'type' => 'date',
            'format' => 'epoch_second',
          ],
          'status' => ['type' => 'boolean'],
          'content' => $type_text + [
              // Trade off index size for better highlighting.
            'term_vector' => 'with_positions_offsets',
          ],
          'rendered_search_result' => [
            'type' => 'keyword',
            'index' => FALSE,
            'store' => TRUE,
          ],
        ],
      ],
    ];

    return $mapping;
  }

}
