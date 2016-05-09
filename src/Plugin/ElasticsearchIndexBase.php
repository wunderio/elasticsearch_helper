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
   * @var Client
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

  public function __construct(array $configuration, $plugin_id, $plugin_definition, Client $client, Serializer $serializer, LoggerInterface $logger) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);

    $this->client = $client;
    $this->serializer = $serializer;
    $this->logger = $logger;
  }

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
   * @inheritdoc
   */
  public function setup() {
    // TODO: create index templates.
    // $this->client->indices()->putTemplate().
  }

  /**
   * @inheritdoc
   */
  public function drop() {
    $params = [
      'index' => $this->indexNamePattern()
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
    } catch (Missing404Exception $e) {
      drupal_set_message($this->t('No Elasticsearch index matching @pattern could be dropped.', [
        '@pattern' => $this->indexNamePattern(),
      ]));
    }
  }

  /**
   * @inheritdoc
   */
  public function index($source) {
    $serialized_data = $this->serialize($source);

    $params = [
      'index' => $this->getIndexName($serialized_data),
      'type' => $this->getTypeName($serialized_data),
      'id' => $this->getId($serialized_data),
      'body' => $serialized_data,
    ];

    $this->client->index($params);
  }

  /**
   * Perform a partial update on a document, or create one if it doesn't exist yet.
   */
  public function upsert($source) {
    $serialized_data = $this->serialize($source);

    $params = [
      'index' => $this->getIndexName($serialized_data),
      'type' => $this->getTypeName($serialized_data),
      'id' => $this->getId($serialized_data),
      'body' => [
        'doc' => $serialized_data,
        'doc_as_upsert' => TRUE,
      ]
    ];

    print_r($this->client->update($params));
  }

  /**
   * @inheritdoc
   */
  public function delete($source) {
    $serialized_data = $this->serialize($source);

    $params = [
      'index' => $this->getIndexName($serialized_data),
      'type' => $this->getTypeName($serialized_data),
      'id' => $this->getId($serialized_data),
    ];

    $this->client->delete($params);
  }

  /**
   * Transform the data from its native format (most likely a Drupal entity) to
   * the format that should be stored in the Elasticsearch index.
   */
  public function serialize($source) {

    if ($source instanceof EntityInterface) {
      // If we have a Drupal entity, use the serializer.
      $data = $this->serializer->normalize($source, 'elasticsearch_helper');

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
   * Replace any placeholders of the form {name} in the given string.
   * @param $haystack
   * @param $data
   */
  private function replacePlaceholders($haystack, $data) {
    // Replace any placeholders with the right value.
    $matches = [];

    if (preg_match_all($this->placeholder_regex, $haystack, $matches)) {
      foreach($matches[0] as $match) {
        $key = substr($match, 1, -1);
        $haystack = str_replace($match, $data[$key], $haystack);
      }
    }

    return $haystack;
  }
}
