<?php

namespace Drupal\elasticsearch_helper_example\Plugin\ElasticsearchIndex;

use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\elasticsearch_helper\Elasticsearch\Index\FieldDefinition;
use Drupal\elasticsearch_helper\Elasticsearch\Index\MappingDefinition;
use Drupal\elasticsearch_helper\ClientInterface;
use Drupal\elasticsearch_helper\ElasticsearchLanguageAnalyzer;
use Drupal\elasticsearch_helper\Plugin\ElasticsearchIndexBase;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Serializer\Serializer;

/**
 * @ElasticsearchIndex(
 *   id = "multilingual_content_index",
 *   label = @Translation("Multilingual content index"),
 *   indexName = "multilingual-{langcode}",
 *   typeName = "node",
 *   entityType = "node"
 * )
 */
class MultilingualContentIndex extends ElasticsearchIndexBase {

  /**
   * @var \Drupal\Core\Language\LanguageManagerInterface
   */
  protected $language_manager;

  /**
   * MultilingualContentIndex constructor.
   *
   * @param array $configuration
   * @param $plugin_id
   * @param $plugin_definition
   * @param \Drupal\elasticsearch_helper\ClientInterface $client
   * @param \Symfony\Component\Serializer\Serializer $serializer
   * @param \Psr\Log\LoggerInterface $logger
   * @param \Drupal\Core\Language\LanguageManagerInterface $language_manager
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, ClientInterface $client, Serializer $serializer, LoggerInterface $logger, LanguageManagerInterface $language_manager) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $client, $serializer, $logger);

    $this->language_manager = $language_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('elasticsearch_helper.client.default'),
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
    foreach ($this->language_manager->getLanguages() as $langcode => $language) {
      // Get index name.
      $index_name = $this->getIndexName(['langcode' => $langcode]);

      // Check if index exists.
      if (!$this->client->indexExists($index_name)) {
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

  /**
   * {@inheritdoc}
   */
  public function getMappingDefinition(array $context = []) {
    // Define only one field. Other fields will be created dynamically.
    return MappingDefinition::create()
      ->addProperty('title', FieldDefinition::create('text'));
  }

}
