<?php
namespace Drupal\elasticsearch_helper_content\Plugin\ElasticsearchIndex;

use Drupal\elasticsearch_helper\ElasticsearchLanguageAnalyzer;
use Drupal\elasticsearch_helper\Plugin\ElasticsearchIndexBase;
use Drupal\elasticsearch_helper_content\ElasticsearchDataTypeDefinition;
use Drupal\elasticsearch_helper_content\ElasticsearchEntityNormalizerManagerInterface;
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
class ElasticsearchContentIndex extends ElasticsearchIndexBase {

  /**
   * @var \Drupal\elasticsearch_helper_content\ElasticsearchEntityNormalizerManagerInterface
   */
  protected $elasticsearchEntityNormalizerManager;

  /**
   * ElasticsearchEntityContentIndex constructor.
   *
   * @param array $configuration
   * @param $plugin_id
   * @param $plugin_definition
   * @param \Elasticsearch\Client $client
   * @param \Symfony\Component\Serializer\Serializer $serializer
   * @param \Psr\Log\LoggerInterface $logger
   * @param \Drupal\elasticsearch_helper_content\ElasticsearchEntityNormalizerManagerInterface $elasticsearch_entity_normalizer_manager
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, Client $client, Serializer $serializer, LoggerInterface $logger, ElasticsearchEntityNormalizerManagerInterface $elasticsearch_entity_normalizer_manager) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $client, $serializer, $logger);

    $this->elasticsearchEntityNormalizerManager = $elasticsearch_entity_normalizer_manager;
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
      $container->get('plugin.manager.elasticsearch_entity_normalizer')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function setup() {
    try {
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
    } catch (\Exception $e) {
      watchdog_exception('elasticsearch_helper_content', $e);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function serialize($source, $context = []) {
    $data = [];

    $index_configuration = $this->getIndexConfiguration();

    $normalizer_configuration = array_merge([
      'entity_type' => $this->pluginDefinition['entityType'],
      'bundle' => $this->pluginDefinition['bundle'],
    ], $index_configuration);

    try {
      /** @var \Drupal\elasticsearch_helper_content\ElasticsearchNormalizerInterface $normalizer_instance */
      $normalizer_instance = $this->elasticsearchEntityNormalizerManager->createInstance($index_configuration['normalizer'], $normalizer_configuration);
      $data = array_merge($data, $normalizer_instance->normalize($source, $context));
    } catch (\Exception $e) {
      watchdog_exception('elasticsearch_helper_content', $e);
    }

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
      'index' => $this->pluginDefinition['indexName'],
      'type' => $this->pluginDefinition['typeName'],
      'body' => [
        'properties' => [],
      ],
    ];

    $index_configuration = $this->getIndexConfiguration();

    $normalizer_configuration = array_merge([
      'entity_type' => $this->pluginDefinition['entityType'],
      'bundle' => $this->pluginDefinition['bundle'],
    ], $index_configuration);

    try {
      /** @var \Drupal\elasticsearch_helper_content\ElasticsearchNormalizerInterface $normalizer_instance */
      $normalizer_instance = $this->elasticsearchEntityNormalizerManager->createInstance($index_configuration['normalizer'], $normalizer_configuration);

      // Loop over property definitions.
      foreach ($normalizer_instance->getPropertyDefinitions() as $field_name => $property) {
        // Add field and field definition.
        $mapping['body']['properties'][$field_name] = $this->preparePropertyItem($property, $mapping_context);
      }
    } catch (\Exception $e) {
      watchdog_exception('elasticsearch_helper_content', $e);
    }

    return $mapping;
  }

  /**
   * Property item iterator.
   *
   * @param \Drupal\elasticsearch_helper_content\ElasticsearchDataTypeDefinition $property_item
   * @param array $mapping_context
   *
   * @see \Drupal\elasticsearch_helper_content\Plugin\ElasticsearchIndex\ElasticsearchContentIndex::preparePropertyItem()
   *
   * @return array
   */
  protected function propertyItemIterator(ElasticsearchDataTypeDefinition $property_item, array $mapping_context) {
    // Add analyzer option to the data definition.
    if (!empty($mapping_context['analyzer']) && $property_item->getDataType() == 'text') {
      $property_item->addOptions(['analyzer' => $mapping_context['analyzer']]);
    }

    return $property_item->getDefinition();
  }

  /**
   * Prepares field properties.
   *
   * Property definitions provided by Elasticsearch entity normalizers can
   * can contain a single definition or an array of definitions.
   *
   * @param \Drupal\elasticsearch_helper_content\ElasticsearchDataTypeDefinition|array $property
   * @param array $mapping_context
   *
   * @return array
   */
  protected function preparePropertyItem($property, array $mapping_context) {
    $result = [];

    if ($property instanceof ElasticsearchDataTypeDefinition) {
      $result = $this->propertyItemIterator($property, $mapping_context);
    }
    // Handle situation when property is multi-value property.
    elseif (is_array($property)) {
      /** @var \Drupal\elasticsearch_helper_content\ElasticsearchDataTypeDefinition $property_item */
      foreach ($property as $property_name => $property_item) {
        $result['properties'][$property_name] = $this->propertyItemIterator($property_item, $mapping_context);
      }
    }

    return $result;
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
