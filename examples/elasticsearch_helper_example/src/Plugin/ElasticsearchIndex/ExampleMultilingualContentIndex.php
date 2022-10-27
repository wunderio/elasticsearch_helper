<?php

namespace Drupal\elasticsearch_helper_example\Plugin\ElasticsearchIndex;

use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\elasticsearch_helper\ElasticsearchLanguageAnalyzer;
use Drupal\elasticsearch_helper\Event\ElasticsearchOperations;
use Elastic\Elasticsearch\Client;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Serializer\Serializer;

/**
 * @ElasticsearchIndex(
 *   id = "example_multilingual_content_index",
 *   label = @Translation("Example multilingual content index"),
 *   indexName = "example-multilingual-{langcode}",
 *   entityType = "node"
 * )
 */
class ExampleMultilingualContentIndex extends IndexBase {

  /**
   * The language manager instance.
   *
   * @var \Drupal\Core\Language\LanguageManagerInterface
   */
  protected $languageManager;

  /**
   * ExampleMultilingualContentIndex constructor.
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
   *
   * @param \Drupal\node\Entity\Node $source
   */
  public function serialize($source, $context = []) {
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
    try {
      // Create one index per language, so that we can have different analyzers.
      foreach ($this->languageManager->getLanguages() as $langcode => $language) {
        // Get index name.
        $index_name = $this->getIndexName(['langcode' => $langcode]);

        // Check if index exists.
        if (!$this->client->indices()->exists(['index' => $index_name])->asBool()) {
          // Get index definition.
          $index_definition = $this->getIndexDefinition(['langcode' => $langcode]);

          // Get analyzer for the language.
          $analyzer = ElasticsearchLanguageAnalyzer::get($langcode);

          // Put analyzer parameter to all "text" fields in the mapping.
          foreach ($index_definition->getMappingDefinition()->getProperties() as $property) {
            if ($property->getDataType()->getType() == 'text') {
              $property->addOption('analyzer', $analyzer);
            }
          }

          $this->createIndex($index_name, $index_definition);
        }
      }
    }
    catch (\Throwable $e) {
      $request_wrapper = isset($request_wrapper) ? $request_wrapper : NULL;
      $this->dispatchOperationErrorEvent($e, ElasticsearchOperations::INDEX_CREATE, $request_wrapper);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getIndexDefinition(array $context = []) {
    // Get index definition.
    $index_definition = parent::getIndexDefinition($context);

    // Get analyzer for the language.
    $analyzer = ElasticsearchLanguageAnalyzer::get($context['langcode']);

    // Add custom settings.
    $index_definition->getSettingsDefinition()->addOptions([
      'analysis' => [
        'analyzer' => [
          $analyzer => [
            'tokenizer' => 'standard',
          ],
        ],
      ],
    ]);

    return $index_definition;
  }

}
