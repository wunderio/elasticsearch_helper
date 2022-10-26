<?php

namespace Drupal\elasticsearch_helper_test\Plugin\ElasticsearchIndex;

use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\elasticsearch_helper\Elasticsearch\Index\FieldDefinition;
use Drupal\elasticsearch_helper\Elasticsearch\Index\MappingDefinition;
use Drupal\elasticsearch_helper\Plugin\ElasticsearchIndexBase;
use Elastic\Elasticsearch\Client;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Serializer\Serializer;

/**
 * @ElasticsearchIndex(
 *   id = "test_multilingual_node_index",
 *   label = @Translation("Test multilingual node index"),
 *   indexName = "test-multilingual-node-index-{langcode}",
 *   entityType = "node",
 *   normalizerFormat = "elasticsearch_helper_test"
 * )
 */
class TestMultilingualNodeIndex extends ElasticsearchIndexBase {

  /**
   * Language manager instance.
   *
   * @var \Drupal\Core\Language\LanguageManagerInterface
   */
  protected $languageManager;

  /**
   * MultilingualContentIndex constructor.
   *
   * @param array $configuration
   * @param $plugin_id
   * @param $plugin_definition
   * @param \Elastic\Elasticsearch\Client $client
   * @param \Symfony\Component\Serializer\Serializer $serializer
   * @param \Psr\Log\LoggerInterface $logger
   * @param \Drupal\Core\Language\LanguageManagerInterface $language_manager
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, Client $client, Serializer $serializer, LoggerInterface $logger, LanguageManagerInterface $language_manager) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $client, $serializer, $logger);

    $this->languageManager = $language_manager;
  }

  /**
   * {@inheritdoc}
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
   * {@inheritdoc}
   */
  public function serialize($source, $context = []) {
    /** @var \Drupal\node\NodeInterface $source */

    $data = parent::serialize($source, $context);

    // Add the language code to be used as a token.
    $data['langcode'] = $source->language()->getId();

    return $data;
  }

  /**
   * {@inheritdoc}
   */
  public function index($source) {
    /** @var \Drupal\node\NodeInterface $source */
    foreach ($source->getTranslationLanguages() as $langcode => $language) {
      $translation = $source->getTranslation($langcode);
      parent::index($translation);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function delete($source) {
    /** @var \Drupal\node\NodeInterface $source */
    foreach ($source->getTranslationLanguages() as $langcode => $language) {
      $translation = $source->getTranslation($langcode);
      parent::delete($translation);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function setup() {
    // Create one index per language, so that we can have different analyzers.
    foreach ($this->languageManager->getLanguages() as $langcode => $language) {
      // Get index name.
      $index_name = $this->getIndexName(['langcode' => $langcode]);

      // Check if index exists.
      if (!$this->client->indices()->exists(['index' => $index_name])) {
        // Get index definition.
        $index_definition = $this->getIndexDefinition(['langcode' => $langcode]);
        // Create index.
        $this->createIndex($index_name, $index_definition);
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getMappingDefinition(array $context = []) {
    return MappingDefinition::create()
      ->addProperty('id', FieldDefinition::create('integer'))
      ->addProperty('uuid', FieldDefinition::create('keyword'))
      ->addProperty('title', FieldDefinition::create('text'))
      ->addProperty('status', FieldDefinition::create('boolean'))
      ->addProperty('langcode', FieldDefinition::create('keyword'));
  }

}
