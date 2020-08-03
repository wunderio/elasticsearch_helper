<?php

namespace Drupal\elasticsearch_helper\Plugin;

use Drupal\Component\Plugin\PluginBase;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Messenger\MessengerTrait;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\elasticsearch_helper\Elasticsearch\Index\IndexDefinition;
use Drupal\elasticsearch_helper\Elasticsearch\Index\SettingsDefinition;
use Drupal\elasticsearch_helper\ElasticsearchClientVersion;
use Drupal\elasticsearch_helper\Event\ElasticsearchEvents;
use Drupal\elasticsearch_helper\Event\ElasticsearchOperationEvent;
use Drupal\elasticsearch_helper\Event\ElasticsearchOperationRequestEvent;
use Drupal\elasticsearch_helper\Event\ElasticsearchOperations;
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
  use MessengerTrait;

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
   * @var \Symfony\Component\EventDispatcher\EventDispatcherInterface
   */
  protected $eventDispatcher;

  /**
   * The regular expression used to identify placeholders in index and type names.
   *
   * @var string
   */
  protected $placeholder_regex = '/{[_\-\w\d]*}/';

  /**
   * Default index settings.
   *
   * @var array
   *
   * @see getIndexDefinition()
   */
  protected $defaultIndexSettings = [
    'number_of_shards' => 1,
    'number_of_replicas' => 0,
  ];

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
   * Gets the event dispatcher.
   *
   * @return \Symfony\Component\EventDispatcher\EventDispatcherInterface
   */
  protected function getEventDispatcher() {
    if (!$this->eventDispatcher) {
      $this->eventDispatcher = \Drupal::service('event_dispatcher');
    }
    return $this->eventDispatcher;
  }

  /**
   * {@inheritdoc}
   */
  public function setup() {
    // Create an index if index definition is provided by the index plugin.
    if ($index_definition = $this->getIndexDefinition()) {
      $index_name = $this->getIndexName([]);

      if (!$this->client->indices()->exists(['index' => $index_name])) {
        $params = [
          'index' => $index_name,
          'body' => $index_definition->toArray(),
        ];

        // Create the index.
        $operation = ElasticsearchOperations::INDEX_CREATE;
        $request_event = new ElasticsearchOperationRequestEvent([$this->client->indices(), 'create'], [$params], $operation, $this);
        $this->getEventDispatcher()->dispatch(ElasticsearchEvents::OPERATION_REQUEST, $request_event);

        return call_user_func_array($request_event->getCallback(), $request_event->getCallbackParameters());
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getIndexDefinition(array $context = []) {
    $settings_definition = SettingsDefinition::create()
      ->addOptions($this->defaultIndexSettings);
    $mapping_definition = $this->getMappingDefinition($context);

    $index_definition = IndexDefinition::create()
      ->setSettingsDefinition($settings_definition)
      ->setMappingDefinition($mapping_definition);

    // If you are using Elasticsearch < 7, add the type to the index definition.
    $index_definition->setType($this->getTypeName([]));

    return $index_definition;
  }

  /**
   * {@inheritdoc}
   */
  public function getExistingIndices() {
    try {
      $params = [
        'index' => $this->indexNamePattern(),
      ];

      // Get a list of indices.
      $operation = ElasticsearchOperations::INDEX_GET;
      $request_event = new ElasticsearchOperationRequestEvent([$this->client->indices(), 'get'], [$params], $operation, $this);
      $this->getEventDispatcher()->dispatch(ElasticsearchEvents::OPERATION_REQUEST, $request_event);

      $result = call_user_func_array($request_event->getCallback(), $request_event->getCallbackParameters());

      return array_keys($result);
    }
    catch (\Exception $e) {
    }

    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function drop() {
    try {
      $params = [
        'index' => $this->indexNamePattern(),
      ];

      if ($indices = $this->client->indices()->get($params)) {
        // Notify user that indices have been deleted.
        foreach ($indices as $indexName => $index) {
          $this->messenger()->addStatus($this->t('Index @indexName is queued for removal.', ['@indexName' => $indexName]));
        }

        // Delete matching indices.
        $operation = ElasticsearchOperations::INDEX_DROP;
        $request_event = new ElasticsearchOperationRequestEvent([$this->client->indices(), 'delete'], [$params], $operation, $this);
        $this->getEventDispatcher()->dispatch(ElasticsearchEvents::OPERATION_REQUEST, $request_event);

        return call_user_func_array($request_event->getCallback(), $request_event->getCallbackParameters());
      }
    }
    catch (Missing404Exception $e) {
      $this->messenger()->addStatus($this->t('No Elasticsearch index matching @pattern could be dropped.', [
        '@pattern' => $this->indexNamePattern(),
      ]));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function index($source) {
    $operation = ElasticsearchOperations::DOCUMENT_INDEX;
    $operation_event = new ElasticsearchOperationEvent($operation, $source, $this);
    $this->getEventDispatcher()->dispatch(ElasticsearchEvents::OPERATION, $operation_event);

    if ($source = $operation_event->getObject()) {
      $serialized_data = $this->serialize($source, ['method' => 'index']);

      $params = [
        'index' => $this->getIndexName($serialized_data),
        'type' => $this->getTypeName($serialized_data),
        'body' => $serialized_data,
      ];

      if ($id = $this->getId($serialized_data)) {
        $params['id'] = $id;
      }

      $request_event = new ElasticsearchOperationRequestEvent([$this->client, 'index'], [$params], $operation, $this);
      $this->getEventDispatcher()->dispatch(ElasticsearchEvents::OPERATION_REQUEST, $request_event);

      return call_user_func_array($request_event->getCallback(), $request_event->getCallbackParameters());
    }

    return NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function get($source) {
    $operation = ElasticsearchOperations::DOCUMENT_GET;
    $operation_event = new ElasticsearchOperationEvent($operation, $source, $this);
    $this->getEventDispatcher()->dispatch(ElasticsearchEvents::OPERATION, $operation_event);

    if ($source = $operation_event->getObject()) {
      $serialized_data = $this->serialize($source, ['method' => 'get']);

      $params = [
        'index' => $this->getIndexName($serialized_data),
        'type' => $this->getTypeName($serialized_data),
        'id' => $this->getId($serialized_data),
      ];

      $request_event = new ElasticsearchOperationRequestEvent([$this->client, 'get'], [$params], $operation, $this);
      $this->getEventDispatcher()->dispatch(ElasticsearchEvents::OPERATION_REQUEST, $request_event);

      return call_user_func_array($request_event->getCallback(), $request_event->getCallbackParameters());
    }

    return NULL;
  }

  /**
   * Perform a partial update on a document, or create one if it doesn't exist yet.
   *
   * @param mixed $source
   *
   * @return array|null
   */
  public function upsert($source) {
    $operation = ElasticsearchOperations::DOCUMENT_UPSERT;
    $operation_event = new ElasticsearchOperationEvent($operation, $source, $this);
    $this->getEventDispatcher()->dispatch(ElasticsearchEvents::OPERATION, $operation_event);

    if ($source = $operation_event->getObject()) {
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

      $request_event = new ElasticsearchOperationRequestEvent([$this->client, 'update'], [$params], $operation, $this);
      $this->getEventDispatcher()->dispatch(ElasticsearchEvents::OPERATION_REQUEST, $request_event);

      return call_user_func_array($request_event->getCallback(), $request_event->getCallbackParameters());
    }

    return NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function delete($source) {
    $operation = ElasticsearchOperations::DOCUMENT_DELETE;
    $operation_event = new ElasticsearchOperationEvent($operation, $source, $this);
    $this->getEventDispatcher()->dispatch(ElasticsearchEvents::OPERATION, $operation_event);

    if ($source = $operation_event->getObject()) {
      $serialized_data = $this->serialize($source, ['method' => 'delete']);

      $params = [
        'index' => $this->getIndexName($serialized_data),
        'type' => $this->getTypeName($serialized_data),
        'id' => $this->getId($serialized_data),
      ];

      try {
        $request_event = new ElasticsearchOperationRequestEvent([$this->client, 'delete'], [$params], $operation, $this);
        $this->getEventDispatcher()->dispatch(ElasticsearchEvents::OPERATION_REQUEST, $request_event);

        return call_user_func_array($request_event->getCallback(), $request_event->getCallbackParameters());
      }
      catch (Missing404Exception $e) {
        $this->logger->notice('Could not delete entry with id @id from Elasticsearch index', [
          '@id' => $params['id'],
        ]);
      }
    }

    return NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function search($params) {
    $params = [
      'index' => $this->indexNamePattern(),
      'type' => $this->typeNamePattern(),
    ] + $params;

    $operation = ElasticsearchOperations::QUERY_SEARCH;
    $request_event = new ElasticsearchOperationRequestEvent([$this->client, 'search'], [$params], $operation, $this);
    $this->getEventDispatcher()->dispatch(ElasticsearchEvents::OPERATION_REQUEST, $request_event);

    return call_user_func_array($request_event->getCallback(), $request_event->getCallbackParameters());
  }

  /**
   * {@inheritdoc}
   */
  public function msearch($params) {
    $params = [
      'index' => $this->indexNamePattern(),
      'type' => $this->typeNamePattern(),
    ] + $params;

    $operation = ElasticsearchOperations::QUERY_MULTI_SEARCH;
    $request_event = new ElasticsearchOperationRequestEvent([$this->client, 'msearch'], [$params], $operation, $this);
    $this->getEventDispatcher()->dispatch(ElasticsearchEvents::OPERATION_REQUEST, $request_event);

    return call_user_func_array($request_event->getCallback(), $request_event->getCallbackParameters());
  }

  /**
   * {@inheritdoc}
   */
  public function bulk($body) {
    $operation = ElasticsearchOperations::DOCUMENT_BULK;
    $operation_event = new ElasticsearchOperationEvent($operation, $body, $this);
    $this->getEventDispatcher()->dispatch(ElasticsearchEvents::OPERATION, $operation_event);

    if ($body = $operation_event->getObject()) {
      $serialized_data = $this->serialize($body, ['method' => 'bulk']);

      $params = [
        'index' => $this->getIndexName($serialized_data),
        'type' => $this->getTypeName($serialized_data),
        'body' => $serialized_data,
      ];

      $request_event = new ElasticsearchOperationRequestEvent([$this->client, 'bulk'], [$params], $operation, $this);
      $this->getEventDispatcher()->dispatch(ElasticsearchEvents::OPERATION_REQUEST, $request_event);

      return call_user_func_array($request_event->getCallback(), $request_event->getCallbackParameters());
    }

    return NULL;
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
   * @param array $data
   *
   * @return string
   */
  public function getIndexName(array $data = []) {
    return $this->replacePlaceholders($this->pluginDefinition['indexName'], $data);
  }

  /**
   * Determine the name of the type where the given data will be indexed.
   *
   * @param array $data
   *
   * @return string
   */
  public function getTypeName(array $data = []) {
    // Set the default type to prevent throwing notice errors.
    if (ElasticsearchClientVersion::getMajorVersion() >= 7) {
      return static::TYPE_DEFAULT;
    }

    return $this->replacePlaceholders($this->pluginDefinition['typeName'], $data);
  }

  /**
   * Determine the name of the ID for the elasticsearch entry.
   *
   * @param array $data
   *
   * @return string
   */
  public function getId(array $data = []) {
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
   * @param array $data
   *
   * @return string
   */
  protected function replacePlaceholders($haystack, array $data) {
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
