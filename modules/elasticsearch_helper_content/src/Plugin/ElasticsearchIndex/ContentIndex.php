<?php

namespace Drupal\elasticsearch_helper_content\Plugin\ElasticsearchIndex;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\elasticsearch_helper\ElasticsearchLanguageAnalyzer;
use Drupal\elasticsearch_helper\Plugin\ElasticsearchIndexBase;
use Drupal\elasticsearch_helper_content\ElasticsearchDataTypeDefinition;
use Elasticsearch\Client;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Serializer\Serializer;

/**
 * @ElasticsearchIndex(
 *   id = "content_index",
 *   deriver = "Drupal\elasticsearch_helper_content\Plugin\Deriver\ContentIndexDeriver"
 * )
 */
class ContentIndex extends ElasticsearchIndexBase {

  /**
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * @var \Drupal\Core\Language\LanguageManagerInterface
   */
  protected $languageManager;

  /**
   * @var \Drupal\elasticsearch_helper_content\ElasticsearchNormalizerInterface[]
   */
  protected $normalizerInstances;

  /**
   * @var \Drupal\elasticsearch_helper_content\ElasticsearchContentIndexInterface|null
   */
  protected $indexEntity;

  /**
   * ContentIndex constructor.
   *
   * @param array $configuration
   * @param $plugin_id
   * @param $plugin_definition
   * @param \Elasticsearch\Client $client
   * @param \Symfony\Component\Serializer\Serializer $serializer
   * @param \Psr\Log\LoggerInterface $logger
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   * @param \Drupal\Core\Language\LanguageManagerInterface $language_manager
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, Client $client, Serializer $serializer, LoggerInterface $logger, EntityTypeManagerInterface $entity_type_manager, LanguageManagerInterface $language_manager) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $client, $serializer, $logger);

    $this->entityTypeManager = $entity_type_manager;
    $this->languageManager = $language_manager;

    $this->indexEntity = $this->getContentIndexEntity();

    // Add language placeholder to index name if index supports multiple
    // languages.
    if ($this->indexEntity->isMultilingual()) {
      $this->pluginDefinition['indexName'] .= '_{langcode}';
    }
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
      $container->get('entity_type.manager'),
      $container->get('language_manager')
    );
  }

  /**
   * Returns Elasticsearch content index entity.
   *
   * @return \Drupal\elasticsearch_helper_content\ElasticsearchContentIndexInterface|null
   */
  public function getContentIndexEntity() {
    try {
      /** @var \Drupal\elasticsearch_helper_content\ElasticsearchContentIndexInterface $entity */
      $entity = $this->entityTypeManager->getStorage('elasticsearch_content_index')->load($this->pluginDefinition['index_entity_id']);
    }
    catch (\Exception $e) {
      watchdog_exception('elasticsearch_helper_content', $e);
      $entity = NULL;
    }

    return $entity;
  }

  /**
   * Returns a list of index names this plugin produces.
   *
   * List is keyed by language code.
   *
   * @return array
   */
  public function getIndexNames() {
    if ($this->indexEntity->isMultilingual()) {
      $index_names = [];

      foreach (\Drupal::service('language_manager')->getLanguages() as $language) {
        $langcode = $language->getId();
        $index_names[$langcode] = $this->getIndexName(['langcode' => $langcode]);
      }
    }
    else {
      $index_names = [NULL => $this->getIndexName([])];
    }

    return $index_names;
  }

  /**
   * {@inheritdoc}
   */
  public function setup() {
    try {
      foreach ($this->getIndexNames() as $langcode => $index_name) {
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
            'langcode' => $langcode,
            'analyzer' => $analyzer,
          ];
          $mapping = $this->provideMapping($mapping_context);

          // Save index mapping.
          $this->client->indices()->putMapping($mapping);
        }
      }
    }
    catch (\Exception $e) {
      watchdog_exception('elasticsearch_helper_content', $e);
    }
  }

  /**
   * {@inheritdoc}
   *
   * @param \Drupal\Core\Entity\ContentEntityInterface $source
   */
  public function index($source) {
    if ($this->indexEntity->isMultilingual()) {
      foreach ($source->getTranslationLanguages() as $langcode => $language) {
        if ($source->hasTranslation($langcode)) {
          parent::index($source->getTranslation($langcode));
        }
      }
    }
    else {
      parent::index($source);
    }
  }

  /**
   * {@inheritdoc}
   *
   * @param \Drupal\Core\Entity\ContentEntityInterface $source
   */
  public function delete($source) {
    if ($this->indexEntity->isMultilingual()) {
      foreach ($source->getTranslationLanguages() as $langcode => $language) {
        if ($source->hasTranslation($langcode)) {
          parent::delete($source->getTranslation($langcode));
        }
      }
    }
    else {
      parent::delete($source);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function serialize($source, $context = []) {
    $data = [];

    try {
      if ($this->indexEntity) {
        $normalizer_instance = $this->indexEntity->getNormalizerInstance();
        $data = $normalizer_instance->normalize($source, $context);
      }
    }
    catch (\Exception $e) {
      watchdog_exception('elasticsearch_helper_content', $e);
    }

    // Add the language code to be used as a token.
    $data['langcode'] = $source->language()->getId();

    return $data;
  }

  /**
   * Returns field mapping.
   *
   * @param array $mapping_context
   *
   * @return array
   */
  protected function provideMapping(array $mapping_context) {
    $mapping = [
      'index' => $this->getIndexName($mapping_context),
      'type' => $this->getTypeName($mapping_context),
      'body' => [
        'properties' => [],
      ],
    ];

    try {
      $normalizer_instance = $this->indexEntity->getNormalizerInstance();

      // Loop over property definitions.
      foreach ($normalizer_instance->getPropertyDefinitions() as $field_name => $property) {
        // Add field and field definition.
        $mapping['body']['properties'][$field_name] = $this->propertyDefinitionIterator($property, $mapping_context);
      }
    }
    catch (\Exception $e) {
      watchdog_exception('elasticsearch_helper_content', $e);
    }

    return $mapping;
  }

  /**
   * Property definition iterator.
   *
   * @param \Drupal\elasticsearch_helper_content\ElasticsearchDataTypeDefinition $property
   * @param array $mapping_context
   *
   * @return array
   */
  protected function propertyDefinitionIterator(ElasticsearchDataTypeDefinition $property, array $mapping_context) {
    // Add analyzer option to the data definition.
    if (!empty($mapping_context['analyzer']) && $property->getDataType() == 'text') {
      $property->addOptions(['analyzer' => $mapping_context['analyzer']]);
    }

    foreach ($property->getProperties() as $property_item) {
      $this->propertyDefinitionIterator($property_item, $mapping_context);
    }

    foreach ($property->getFields() as $property_item) {
      $this->propertyDefinitionIterator($property_item, $mapping_context);
    }

    return $property->getDefinition();
  }

}
