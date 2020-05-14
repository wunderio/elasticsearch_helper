<?php

namespace Drupal\elasticsearch_helper_content\Plugin\ElasticsearchIndex;

use Drupal\Core\Entity\EntityPublishedInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\elasticsearch_helper\ElasticsearchLanguageAnalyzer;
use Drupal\elasticsearch_helper\Plugin\ElasticsearchIndexBase;
use Drupal\elasticsearch_helper_content\ElasticsearchDataTypeDefinition;
use Drupal\elasticsearch_helper_content\Entity\ElasticsearchContentIndex;
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
    if ($this->isMultilingual()) {
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
   * Returns TRUE if content is multilingual.
   *
   * Multilingual configuration is taken from plugin definition which enables
   * other modules to change the behaviour of the plugin by instantiating
   * the plugin directly.
   *
   * @return bool
   *
   * @see \Drupal\elasticsearch_helper_content\Plugin\Deriver\ContentIndexDeriver::getDerivativeDefinitions()
   */
  protected function isMultilingual() {
    return !empty($this->pluginDefinition['multilingual']);
  }

  /**
   * Returns a list of index names this plugin produces.
   *
   * List is keyed by language code.
   *
   * @return array
   */
  public function getIndexNames() {
    if ($this->isMultilingual()) {
      $index_names = [];

      foreach ($this->languageManager->getLanguages() as $language) {
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
          $setup_configuration = $this->getIndexSetupSettings($index_name, $langcode);
          $this->client->indices()->create($setup_configuration);

          // Get default analyzer for the language.
          $analyzer = $this->getDefaultLanguageAnalyzer($langcode);

          // Assemble field mapping for index.
          $mapping_context = [
            'langcode' => $langcode,
            'analyzer' => $analyzer,
          ];
          $mapping = $this->getMappingSettings($mapping_context);

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
   * Returns index setup settings.
   *
   * @param $index_name
   * @param string|null $langcode
   *
   * @return array
   */
  protected function getIndexSetupSettings($index_name, $langcode = NULL) {
    return [
      'index' => $index_name,
      'body' => [
        'number_of_shards' => 1,
        'number_of_replicas' => 0,
      ],
    ];
  }

  /**
   * Returns default analyzer for given language.
   *
   * @param string|null $langcode
   *
   * @return string
   */
  protected function getDefaultLanguageAnalyzer($langcode = NULL) {
    return ElasticsearchLanguageAnalyzer::get($langcode);
  }

  /**
   * {@inheritdoc}
   *
   * @param \Drupal\Core\Entity\ContentEntityInterface $source
   */
  public function index($source) {
    if ($this->isMultilingual()) {
      foreach ($source->getTranslationLanguages() as $langcode => $language) {
        if ($source->hasTranslation($langcode)) {
          $translation = $source->getTranslation($langcode);
          $this->indexOrDeleteTranslation($translation);
        }
      }
    }
    else {
      $this->indexOrDeleteTranslation($source);
    }
  }

  /**
   * Returns TRUE if entity is publishing status aware.
   *
   * @param \Drupal\Core\Entity\ContentEntityInterface $source
   *
   * @return bool
   */
  protected function isPublishAware($source) {
    return $source instanceof EntityPublishedInterface;
  }

  /**
   * Returns TRUE if translation of the entity should be added to the index.
   *
   * @param $source
   *
   * @return bool
   */
  protected function isIndexable($source) {
    $index_unpublished = $this->indexEntity->indexUnpublishedContent();

    // Return TRUE if entity type does not support publishing status or
    // unpublished content should be indexed.
    if (in_array($index_unpublished, [ElasticsearchContentIndex::INDEX_UNPUBLISHED_NA, ElasticsearchContentIndex::INDEX_UNPUBLISHED], TRUE)) {
      return TRUE;
    }

    if ($index_unpublished == ElasticsearchContentIndex::INDEX_UNPUBLISHED_IGNORE) {
      if ($this->isPublishAware($source) && $source->isPublished()) {
        return TRUE;
      }
    }

    // Stay on the safe side and do not index by default.
    return FALSE;
  }

  /**
   * Returns TRUE if translation of the entity should be removed from the index.
   *
   * @param $source
   *
   * @return bool
   */
  protected function isDeletable($source) {
    $index_unpublished = $this->indexEntity->indexUnpublishedContent();

    // Return FALSE if entity type does not support publishing status or
    // unpublished content should be indexed.
    if (in_array($index_unpublished, [ElasticsearchContentIndex::INDEX_UNPUBLISHED_NA, ElasticsearchContentIndex::INDEX_UNPUBLISHED], TRUE)) {
      return FALSE;
    }

    if ($index_unpublished == ElasticsearchContentIndex::INDEX_UNPUBLISHED_IGNORE) {
      if ($this->isPublishAware($source) && $source->isPublished()) {
        return FALSE;
      }
    }

    // Stay on the safe side and remove by default.
    return TRUE;
  }

  /**
   * Indexes or removes translation of the entity.
   *
   * @param \Drupal\Core\Entity\ContentEntityInterface $source
   */
  protected function indexOrDeleteTranslation($source) {
    if ($this->isIndexable($source)) {
      // Parent method is called here as this method is invoked from index().
      parent::index($source);
    }
    elseif ($this->isDeletable($source)) {
      $this->deleteTranslation($source);
    }
  }

  /**
   * {@inheritdoc}
   *
   * @param \Drupal\Core\Entity\ContentEntityInterface $source
   */
  public function delete($source) {
    if ($this->isMultilingual()) {
      foreach ($source->getTranslationLanguages() as $langcode => $language) {
        if ($source->hasTranslation($langcode)) {
          $translation = $source->getTranslation($langcode);
          $this->deleteTranslation($translation);
        }
      }
    }
    else {
      $this->deleteTranslation($source);
    }
  }

  /**
   * Removes translation of the entity.
   *
   * @param \Drupal\Core\Entity\ContentEntityInterface $source
   */
  public function deleteTranslation($source) {
    parent::delete($source);
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
   * Returns field mapping settings.
   *
   * @param array $mapping_context
   *
   * @return array
   */
  protected function getMappingSettings(array $mapping_context) {
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
