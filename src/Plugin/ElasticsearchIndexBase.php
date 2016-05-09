<?php

namespace Drupal\elasticsearch_helper\Plugin;

use Drupal\Component\Plugin\PluginBase;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Elasticsearch\Client;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Serializer\Serializer;

/**
 * Base class for Elasticsearch index plugins.
 */
abstract class ElasticsearchIndexBase extends PluginBase implements ElasticsearchIndexInterface, ContainerFactoryPluginInterface {

  /**
   * @var Client
   */
  protected $client;

  /**
   * @var \Symfony\Component\Serializer\Serializer
   */
  protected $serializer;

  /**
   * The regular expression used to identify placeholders in index and type names.
   *
   * @var string
   */
  protected $placeholder_regex = '/{[_\-\w\d]*}/';

  public function __construct(array $configuration, $plugin_id, $plugin_definition, Client $client, Serializer $serializer) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);

    $this->client = $client;
    $this->serializer = $serializer;
  }

  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('elasticsearch_helper.elasticsearch_client'),
      $container->get('serializer')
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
    $this->client->indices()->delete([
      'index' => $this->indexNamePattern()
    ]);
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
    if (isset($source['id']) && (is_string($source['id']) || is_numeric($source['id']))) {
      // If there is an attribute with the key 'id', use it.
      return $source['id'];
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
