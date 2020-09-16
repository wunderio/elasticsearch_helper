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
use Drupal\elasticsearch_helper\Event\ElasticsearchOperationErrorEvent;
use Drupal\elasticsearch_helper\Event\ElasticsearchHelperEvents;
use Drupal\elasticsearch_helper\Event\ElasticsearchHelperGenericEvent;
use Drupal\elasticsearch_helper\Event\ElasticsearchOperationEvent;
use Drupal\elasticsearch_helper\Event\ElasticsearchOperationRequestEvent;
use Drupal\elasticsearch_helper\Event\ElasticsearchOperationResultEvent;
use Drupal\elasticsearch_helper\Event\ElasticsearchOperations;
use Elasticsearch\Client;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Serializer\Serializer;
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
   * @var \Drupal\elasticsearch_helper\Plugin\ElasticsearchIndexManager
   */
  protected $indexPluginManager;

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
   * Returns the event dispatcher.
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
   * Returns Elasticsearch index plugin manager.
   *
   * @return \Drupal\elasticsearch_helper\Plugin\ElasticsearchIndexManager
   */
  protected function getElasticsearchIndexPluginManager() {
    if (!$this->indexPluginManager) {
      $this->indexPluginManager = \Drupal::service('plugin.manager.elasticsearch_index.processor');
    }

    return $this->indexPluginManager;
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
   * Creates Elasticsearch operations event.
   *
   * @param $operation
   * @param mixed $source
   *
   * @return \Drupal\elasticsearch_helper\Event\ElasticsearchOperationEvent
   */
  protected function dispatchOperationEvent($operation, $source = NULL) {
    $event = new ElasticsearchOperationEvent($operation, $source, $this);
    $this->getEventDispatcher()->dispatch(ElasticsearchEvents::OPERATION, $event);

    return $event;
  }

  /**
   * Creates Elasticsearch request operation event.
   *
   * @param $operation
   * @param $callback
   * @param $params
   * @param mixed $source
   *
   * @return \Drupal\elasticsearch_helper\Event\ElasticsearchOperationRequestEvent
   */
  protected function dispatchOperationRequestEvent($operation, $callback, $params, $source = NULL) {
    $event = new ElasticsearchOperationRequestEvent($operation, $callback, [$params], $this);
    $this->getEventDispatcher()->dispatch(ElasticsearchEvents::OPERATION_REQUEST, $event);

    return $event;
  }

  /**
   * Dispatches Elasticsearch operation result event.
   *
   * @param array $result
   * @param $operation
   * @param null $source
   * @param array $request_params
   *
   * @return \Drupal\elasticsearch_helper\Event\ElasticsearchOperationResultEvent
   */
  protected function dispatchOperationResultEvent(array $result, $operation, $source = NULL, $request_params = []) {
    $event = new ElasticsearchOperationResultEvent($result, $operation, $source, $request_params, $this);
    $this->getEventDispatcher()->dispatch(ElasticsearchEvents::OPERATION_RESULT, $event);

    return $event;
  }

  /**
   * Dispatches Elasticsearch operation exception event.
   *
   * @param \Throwable $error
   * @param $operation
   * @param null $source
   * @param array $request_params
   *
   * @return \Drupal\elasticsearch_helper\Event\ElasticsearchOperationErrorEvent
   */
  protected function dispatchOperationErrorEvent(\Throwable $error, $operation, $source = NULL, $request_params = []) {
    $event = new ElasticsearchOperationErrorEvent($error, $operation, $source, $request_params, $this);
    $this->getEventDispatcher()->dispatch(ElasticsearchEvents::OPERATION_ERROR, $event);

    return $event;
  }

  /**
   * Executes Elasticsearch operation callback.
   *
   * @param $operation
   * @param $callback
   * @param $params
   * @param mixed $source
   *
   * @return mixed
   */
  protected function executeCallback($operation, $callback, $params, $source = NULL) {
    $request_event = new ElasticsearchOperationRequestEvent($operation, $callback, [$params], $this);
    $this->getEventDispatcher()->dispatch(ElasticsearchEvents::OPERATION_REQUEST, $request_event);

    return call_user_func_array($request_event->getCallback(), $request_event->getCallbackParameters());
  }

  /**
   * {@inheritdoc}
   */
  public function setup() {
    try {
      // Create an index if index definition is provided by the index plugin.
      if ($index_definition = $this->getIndexDefinition()) {
        $index_name = $this->getIndexName();

        if (!$this->client->indices()->exists(['index' => $index_name])) {
          $this->createIndex($index_name, $index_definition);
        }
      }
    }
    catch (\Throwable $e) {
      $this->dispatchOperationErrorEvent($e, ElasticsearchOperations::INDEX_CREATE);
    }
  }

  /**
   * Creates a single index.
   *
   * @param $index_name
   * @param \Drupal\elasticsearch_helper\Elasticsearch\Index\IndexDefinition $index_definition
   */
  public function createIndex($index_name, IndexDefinition $index_definition) {
    try {
      $operation = ElasticsearchOperations::INDEX_CREATE;

      $request_params = [
        'index' => $index_name,
        'body' => $index_definition->toArray(),
      ];

      // Create the index.
      $callback = [$this->client->indices(), 'create'];
      $result = $this->executeCallback($operation, $callback, $request_params);
      $this->dispatchOperationResultEvent($result, $operation, NULL, $request_params);
    }
    catch (\Throwable $e) {
      $request_params = isset($request_params) ? $request_params : NULL;
      $this->dispatchOperationErrorEvent($e, $operation, NULL, $request_params);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getExistingIndices() {
    try {
      $operation = ElasticsearchOperations::INDEX_GET;

      $request_params = [
        'index' => $this->indexNamePattern(),
      ];

      // Get a list of indices.
      $callback = [$this->client->indices(), 'get'];
      $result = $this->executeCallback($operation, $callback, $request_params);
      $this->dispatchOperationResultEvent($result, $operation, NULL, $request_params);

      return array_keys($result);
    }
    catch (\Throwable $e) {
      $request_params = isset($request_params) ? $request_params : NULL;
      $this->dispatchOperationErrorEvent($e, $operation, NULL, $request_params);

      throw $e;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function drop() {
    try {
      $operation = ElasticsearchOperations::INDEX_DROP;

      $request_params = [
        'index' => $this->indexNamePattern(),
      ];

      if ($indices = $this->client->indices()->get($request_params)) {
        // Notify user that indices have been deleted.
        foreach ($indices as $indexName => $index) {
          $this->messenger()->addStatus($this->t('Index @indexName is queued for removal.', ['@indexName' => $indexName]));
        }

        // Delete matching indices.
        $callback = [$this->client->indices(), 'delete'];
        $result = $this->executeCallback($operation, $callback, $request_params);
        $this->dispatchOperationResultEvent($result, $operation, NULL, $request_params);
      }
    }
    catch (\Throwable $e) {
      $request_params = isset($request_params) ? $request_params : NULL;
      $this->dispatchOperationErrorEvent($e, $operation, NULL, $request_params);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function index($source) {
    try {
      $operation = ElasticsearchOperations::DOCUMENT_INDEX;
      $operation_event = $this->dispatchOperationEvent($operation, $source);

      if ($source = $operation_event->getObject()) {
        $serialized_data = $this->serialize($source, ['method' => 'index']);

        $request_params = [
          'index' => $this->getIndexName($serialized_data),
          'type' => $this->getTypeName($serialized_data),
          'body' => $serialized_data,
        ];

        if ($id = $this->getId($serialized_data)) {
          $request_params['id'] = $id;
        }

        $callback = [$this->client, 'index'];
        $result = $this->executeCallback($operation, $callback, $request_params, $source);
        $this->dispatchOperationResultEvent($result, $operation, $source, $request_params);
      }
    }
    catch (\Throwable $e) {
      $request_params = isset($request_params) ? $request_params : NULL;
      $this->dispatchOperationErrorEvent($e, $operation, $source, $request_params);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function get($source) {
    try {
      $operation = ElasticsearchOperations::DOCUMENT_GET;
      $operation_event = $this->dispatchOperationEvent($operation, $source);

      if ($source = $operation_event->getObject()) {
        $serialized_data = $this->serialize($source, ['method' => 'get']);

        $request_params = [
          'index' => $this->getIndexName($serialized_data),
          'type' => $this->getTypeName($serialized_data),
          'id' => $this->getId($serialized_data),
        ];

        $callback = [$this->client, 'get'];
        $result = $this->executeCallback($operation, $callback, $request_params, $source);
        $this->dispatchOperationResultEvent($result, $operation, $source, $request_params);

        return $result;
      }
    }
    catch (\Throwable $e) {
      $request_params = isset($request_params) ? $request_params : NULL;
      $this->dispatchOperationErrorEvent($e, $operation, $source, $request_params);

      throw $e;
    }
  }

  /**
   * Perform a partial update on a document, or create one if it doesn't exist yet.
   *
   * @param mixed $source
   */
  public function upsert($source) {
    try {
      $operation = ElasticsearchOperations::DOCUMENT_UPSERT;
      $operation_event = $this->dispatchOperationEvent($operation, $source);

      if ($source = $operation_event->getObject()) {
        $serialized_data = $this->serialize($source, ['method' => 'upsert']);

        $request_params = [
          'index' => $this->getIndexName($serialized_data),
          'type' => $this->getTypeName($serialized_data),
          'id' => $this->getId($serialized_data),
          'body' => [
            'doc' => $serialized_data,
            'doc_as_upsert' => TRUE,
          ],
        ];

        $callback = [$this->client, 'update'];
        $result = $this->executeCallback($operation, $callback, $request_params, $source);
        $this->dispatchOperationResultEvent($result, $operation, $source, $request_params);
      }
    }
    catch (\Throwable $e) {
      $request_params = isset($request_params) ? $request_params : NULL;
      $this->dispatchOperationErrorEvent($e, $operation, $source, $request_params);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function delete($source) {
    try {
      $operation = ElasticsearchOperations::DOCUMENT_DELETE;
      $operation_event = $this->dispatchOperationEvent($operation, $source);

      if ($source = $operation_event->getObject()) {
        $serialized_data = $this->serialize($source, ['method' => 'delete']);

        $request_params = [
          'index' => $this->getIndexName($serialized_data),
          'type' => $this->getTypeName($serialized_data),
          'id' => $this->getId($serialized_data),
        ];

        $callback = [$this->client, 'delete'];
        $result = $this->executeCallback($operation, $callback, $request_params, $source);
        $this->dispatchOperationResultEvent($result, $operation, $source, $request_params);
      }
    }
    catch (\Throwable $e) {
      $request_params = isset($request_params) ? $request_params : NULL;
      $this->dispatchOperationErrorEvent($e, $operation, $source, $request_params);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function search($params) {
    try {
      $operation = ElasticsearchOperations::QUERY_SEARCH;

      $request_params = [
        'index' => $this->indexNamePattern(),
        'type' => $this->typeNamePattern(),
      ] + $params;

      $callback = [$this->client, 'search'];
      $result = $this->executeCallback($operation, $callback, $request_params, $params);
      $this->dispatchOperationResultEvent($result, $operation, $params, $request_params);

      return $result;
    }
    catch (\Throwable $e) {
      $request_params = isset($request_params) ? $request_params : NULL;
      $this->dispatchOperationErrorEvent($e, $operation, $params, $request_params);

      throw $e;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function msearch($params) {
    try {
      $operation = ElasticsearchOperations::QUERY_MULTI_SEARCH;

      $request_params = [
        'index' => $this->indexNamePattern(),
        'type' => $this->typeNamePattern(),
      ] + $params;

      $callback = [$this->client, 'msearch'];
      $result = $this->executeCallback($operation, $callback, $request_params, $params);
      $this->dispatchOperationResultEvent($result, $operation, $params, $request_params);

      return $result;
    }
    catch (\Throwable $e) {
      $request_params = isset($request_params) ? $request_params : NULL;
      $this->dispatchOperationErrorEvent($e, $operation, $params, $request_params);

      throw $e;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function bulk($body) {
    try {
      $operation = ElasticsearchOperations::DOCUMENT_BULK;
      $operation_event = $this->dispatchOperationEvent($operation, $body);

      if ($body = $operation_event->getObject()) {
        $serialized_data = $this->serialize($body, ['method' => 'bulk']);

        $request_params = [
          'index' => $this->getIndexName($serialized_data),
          'type' => $this->getTypeName($serialized_data),
          'body' => $serialized_data,
        ];

        $callback = [$this->client, 'bulk'];
        $result = $this->executeCallback($operation, $callback, $request_params, $body);
        $this->dispatchOperationResultEvent($result, $operation, $body, $request_params);
      }
    }
    catch (\Throwable $e) {
      $request_params = isset($request_params) ? $request_params : NULL;
      $this->dispatchOperationErrorEvent($e, $operation, $body, $request_params);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function reindex(array $context = []) {
    if (isset($this->pluginDefinition['entityType'])) {
      try {
        $operation = ElasticsearchHelperEvents::REINDEX;

        $entity_type = $this->pluginDefinition['entityType'];
        $bundle = NULL;

        if (isset($this->pluginDefinition['bundle'])) {
          $bundle = $this->pluginDefinition['bundle'];
        }

        $callback = [$this->getElasticsearchIndexPluginManager(), 'reindexEntities'];
        $params = [$entity_type, $bundle];

        $request_event = new ElasticsearchHelperGenericEvent($callback, $params, $this);
        $this->getEventDispatcher()->dispatch($operation, $request_event);

        call_user_func_array($request_event->getCallback(), $request_event->getCallbackParameters());
      }
      catch (\Throwable $e) {
        $this->dispatchOperationErrorEvent($e, $operation);
      }
    }
  }

  /**
   * Serializes the source object.
   *
   * Transform the data from its native format (most likely a Drupal entity) to
   * the format that should be stored in the Elasticsearch index.
   *
   * @param mixed $source
   * @param array $context
   *
   * @return array
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
  public function indexNamePattern() {
    return preg_replace($this->placeholder_regex, '*', $this->pluginDefinition['indexName']);
  }

  /**
   * Define a pattern that will match all types.
   *
   * @return string
   */
  public function typeNamePattern() {
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
  public function replacePlaceholders($haystack, array $data) {
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
