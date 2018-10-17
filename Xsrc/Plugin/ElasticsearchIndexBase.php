<?php

namespace Drupal\elasticsearch_helper\Plugin;

use Drupal\Component\Plugin\PluginBase;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Elasticsearch\Client;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Serializer\Serializer;
use Elasticsearch\Common\Exceptions\Missing404Exception;
use Psr\Log\LoggerInterface;

/**
 * Base class for Elasticsearch index plugins.
 */
abstract class ElasticsearchIndexBase extends PluginBase implements ElasticsearchIndexInterface, ContainerFactoryPluginInterface {

  use StringTranslationTrait;

  /**
   * @var \Elasticsearch\Client
   */
  protected $client;

  /**
   * @var \Symfony\Component\Serializer\Serializer
   */
  protected $serializer;

  /**
   * @var \Psr\Log\LoggerInterface
   */
  protected $logger;

  /**
   * The regular expression used to identify placeholders in index and type names.
   *
   * @var string
   */
  protected $placeholder_regex = '/{[_\-\w\d]*}/';

  /**
   * ElasticsearchIndexBase constructor.
   * @param array $configuration
   * @param string $plugin_id
   * @param mixed $plugin_definition
   * @param \Elasticsearch\Client $client
   * @param \Symfony\Component\Serializer\Serializer $serializer
   * @param \Psr\Log\LoggerInterface $logger
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, Client $client, Serializer $serializer, LoggerInterface $logger) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);

    $this->client = $client;
    $this->serializer = $serializer;
    $this->logger = $logger;
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
      $container->get('logger.factory')->get('elasticsearch_helper')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getClient() {
    return $this->client;
  }

  /**
   * @inheritdoc
   */
  public function setup() {
    // TODO: create index templates.
    // $this->client->indices()->putTemplate().
  }

  /**
   * @inheritdoc
   */
  public function getExistingIndices() {
    $params = [
      'index' => $this->indexNamePattern(),
    ];

    try {
      return array_keys($this->client->indices()->get($params));
    }
    catch (Missing404Exception $e) {
      return [];
    }
  }

  /**
   * @inheritdoc
   */
  public function drop() {
    $params = [
      'index' => $this->indexNamePattern(),
    ];

    try {
      if ($indices = $this->client->indices()->get($params)) {
        // Notify user that indices have been deleted.
        foreach ($indices as $indexName => $index) {
          drupal_set_message($this->t('Index @indexName has been deleted.', ['@indexName' => $indexName]));
        }

        // Delete matching indices.
        $this->client->indices()->delete($params);
      }
    }
    catch (Missing404Exception $e) {
      drupal_set_message($this->t('No Elasticsearch index matching @pattern could be dropped.', [
        '@pattern' => $this->indexNamePattern(),
      ]));
    }
  }

  /**
   * @inheritdoc
   */
  public function index($source) {
    $serialized_data = $this->serialize($source, ['method' => 'index']);

    $params = [
      'index' => $this->getIndexName($serialized_data),
      'type' => $this->getTypeName($serialized_data),
      'body' => $serialized_data,
    ];

    if ($id = $this->getId($serialized_data)) {
      $params['id'] = $id;
    }

    $this->client->index($params);
  }

  /**
   * {@inheritdoc}
   */
  public function get($source) {
    $serialized_data = $this->serialize($source, ['method' => 'get']);

    $params = [
      'index' => $this->getIndexName($serialized_data),
      'type' => $this->getTypeName($serialized_data),
      'id' => $this->getId($serialized_data),
    ];

    return $this->client->get($params);
  }

  /**
   * Perform a partial update on a document, or create one if it doesn't exist yet.
   */
  public function upsert($source) {
    $serialized_data = $this->serialize($source, ['method' => 'upsert']);

    $params = [
      'index' => $this->getIndexName($serialized_data),
      'type' => $this->getTypeName($serialized_data),
      'id' => $this->getId($serialized_data),
      'body' => [
        'doc' => $serialized_data,
        'doc_as_upsert' => TRUE,
      ],
    ];

    $this->client->update($params);
  }

  /**
   * @inheritdoc
   */
  public function delete($source) {
    $serialized_data = $this->serialize($source, ['method' => 'delete']);

    $params = [
      'index' => $this->getIndexName($serialized_data),
      'type' => $this->getTypeName($serialized_data),
      'id' => $this->getId($serialized_data),
    ];

    try {
      $this->client->delete($params);
    }
    catch (Missing404Exception $e) {
      $this->logger->notice('Could not delete entry with id @id from elasticsearh index', [
        '@id' => $params['id'],
      ]);
    }
  }

  /**
   * @inheritdoc
   */
  public function search($params) {
    return $this->client->search([
      'index' => $this->indexNamePattern(),
      'type' => $this->typeNamePattern(),
    ] + $params);
  }

  /**
   * @inheritdoc
   */
  public function msearch($params) {
    return $this->client->msearch([
      'index' => $this->indexNamePattern(),
      'type' => $this->typeNamePattern(),
    ] + $params);
  }

  /**
   * @inheritdoc
   */
  public function bulk($body) {
    $serialized_data = $this->serialize($body, ['method' => 'bulk']);

    $params = [
      'index' => $this->getIndexName($serialized_data),
      'type' => $this->getTypeName($serialized_data),
      'body' => $serialized_data,
    ];

    $this->client->bulk($params);
  }

  /**
   * Transform the data from its native format (most likely a Drupal entity) to
   * the format that should be stored in the Elasticsearch index.
   */
  public function serialize($source, $context = []) {

    if ($source instanceof EntityInterface) {
      if (isset($this->pluginDefinition['normalizerFormat'])) {
        // Use custom normalizerFormat if it's defined in plugin.
        $format = $this->pluginDefinition['normalizerFormat'];
      }
      else {
        // Use the default normalizer format.
        $format = 'elasticsearch_helper';
      }
      // If we have a Drupal entity, use the serializer.
      $data = $this->serializer->normalize($source, $format, $context);

      // Set the 'id' field to be the entity id,
      // it will be use by the getID() method.
      $data['id'] = $source->id();

      return $data;
    }
    else {
      // Non-entities are simply kept as they are.
      return $source;
    }
  }

  /**
   * Determine the name of the index where the given data will be indexed.
   *
   * @return string
   */
  protected function getIndexName($data) {
    return $this->replacePlaceholders($this->pluginDefinition['indexName'], $data);
  }

  /**
   * Determine the name of the type where the given data will be indexed.
   *
   * @return string
   */
  protected function getTypeName($data) {
    return $this->replacePlaceholders($this->pluginDefinition['typeName'], $data);
  }

  /**
   * Determine the name of the ID for the elasticsearch entry.
   *
   * @return string
   */
  public function getId($data) {

    if (isset($data['id']) && (is_string($data['id']) || is_numeric($data['id']))) {
      // If there is an attribute with the key 'id', use it.
      return $data['id'];
    }
    else {
      // Elasticsearch will generate its own id.
      return NULL;
    }
  }

  /**
   * Define a pattern that will match all indices. This is used for tasks like
   * deleting indices which can be done as one operation.
   *
   * @return string
   */
  protected function indexNamePattern() {
    return preg_replace($this->placeholder_regex, '*', $this->pluginDefinition['indexName']);
  }

  /**
   * Define a pattern that will match all types.
   *
   * @return string
   */
  protected function typeNamePattern() {
    return preg_replace($this->placeholder_regex, '*', $this->pluginDefinition['typeName']);
  }

  /**
   * Replace any placeholders of the form {name} in the given string.
   *
   * @param $haystack
   * @param $data
   */
  private function replacePlaceholders($haystack, $data) {
    // Replace any placeholders with the right value.
    $matches = [];

    if (preg_match_all($this->placeholder_regex, $haystack, $matches)) {
      foreach ($matches[0] as $match) {
        $key = substr($match, 1, -1);
        $haystack = str_replace($match, $data[$key], $haystack);
      }
    }

    return $haystack;
  }

}
