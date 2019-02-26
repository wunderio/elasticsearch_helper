<?php

namespace Drupal\elasticsearch_helper_content\Plugin\ElasticsearchIndex;

use Drupal\elasticsearch_helper_content\ElasticsearchEntityNormalizerManagerInterface;
use Elasticsearch\Client;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Serializer\Serializer;

/**
 * Class ElasticsearchEntityContentIndex
 */
class ElasticsearchEntityContentIndex extends ElasticsearchEntityIndexBase {

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
  protected function provideMapping(array $mapping_context) {
    $mapping = [
      'index' => $this->pluginDefinition['indexName'],
      'type' => $this->pluginDefinition['typeName'],
      'body' => [
        'properties' => [],
      ],
    ];

    $index_configuration = $this->getIndexConfiguration();
    $normalizer = $index_configuration['normalizer'];

    /** @var \Drupal\elasticsearch_helper_content\ElasticsearchNormalizerInterface $normalizer_instance */
    if ($normalizer_instance = $this->elasticsearchEntityNormalizerManager->createInstance($normalizer)) {
      $property_definition_context = [
        'entity_type' => $this->pluginDefinition['entityType'],
        'bundle' => $this->pluginDefinition['bundle'],
      ];

      // Loop over property definitions.
      foreach ($normalizer_instance->getPropertyDefinitions($property_definition_context) as $field_name => $property) {
        // Add analyzer option to the data definition.
        if (!empty($mapping_context['analyzer']) && $property->getDataType() == 'text') {
          $property->addOptions(['analyzer' => $mapping_context['analyzer']]);
        }

        // Add field and field definition.
        $mapping['body']['properties'][$field_name] = $property->getDefinition();
      }
    }

    return $mapping;
  }

}
