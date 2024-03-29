<?php

namespace Drupal\elasticsearch_helper\Plugin;

use Drupal\Component\Plugin\PluginBase;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Messenger\MessengerTrait;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\elasticsearch_helper\Elasticsearch\Index\IndexDefinition;
use Drupal\elasticsearch_helper\Elasticsearch\Index\SettingsDefinition;
use Drupal\elasticsearch_helper\ElasticsearchRequestWrapper;
use Drupal\elasticsearch_helper\ElasticsearchRequestWrapperInterface;
use Drupal\elasticsearch_helper\Event\ElasticsearchEvents;
use Drupal\elasticsearch_helper\Event\ElasticsearchOperationErrorEvent;
use Drupal\elasticsearch_helper\Event\ElasticsearchHelperEvents;
use Drupal\elasticsearch_helper\Event\ElasticsearchHelperCallbackEvent;
use Drupal\elasticsearch_helper\Event\ElasticsearchOperationEvent;
use Drupal\elasticsearch_helper\Event\ElasticsearchOperations;
use Elastic\Elasticsearch\Client;
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
   * @var \Elastic\Elasticsearch\Client
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
   * @param \Elastic\Elasticsearch\Client $client
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

    return $index_definition;
  }

  /**
   * Creates Elasticsearch operation event.
   *
   * @param $operation
   *   The operation being performed.
   * @param mixed|null $object
   *   The index-able object or an index name.
   * @param array $metadata
   *   The metadata related to the object.
   *
   * @return \Drupal\elasticsearch_helper\Event\ElasticsearchOperationEvent
   */
  protected function dispatchOperationEvent($operation, $object = NULL, $metadata = []) {
    $event = new ElasticsearchOperationEvent($operation, $this, $object, $metadata);
    $this->getEventDispatcher()->dispatch($event, ElasticsearchEvents::OPERATION);

    return $event;
  }

  /**
   * Dispatches Elasticsearch operation error event.
   *
   * @param \Throwable $error
   *   The thrown error.
   * @param $operation
   *   The operation being performed.
   * @param \Drupal\elasticsearch_helper\ElasticsearchRequestWrapperInterface|null $request_wrapper
   *   The request wrapper instance.
   * @param mixed|null $object
   *   The index-able object or an index name.
   * @param array $metadata
   *   The metadata related to the object.
   *
   * @return \Drupal\elasticsearch_helper\Event\ElasticsearchOperationErrorEvent
   */
  protected function dispatchOperationErrorEvent(\Throwable $error, $operation, ElasticsearchRequestWrapperInterface $request_wrapper = NULL, $object = NULL, $metadata = []) {
    $event = new ElasticsearchOperationErrorEvent($error, $operation, $this, $request_wrapper, $object, $metadata);
    $this->getEventDispatcher()->dispatch($event, ElasticsearchEvents::OPERATION_ERROR);

    return $event;
  }

  /**
   * Returns Elasticsearch request wrapper instance.
   *
   * @param $operation
   * @param $callback
   * @param array $request_params
   * @param mixed|null $source
   *
   * @return \Drupal\elasticsearch_helper\ElasticsearchRequestWrapperInterface
   */
  protected function createRequest($operation, $callback, array $request_params, $source = NULL) {
    $event_dispatcher = $this->getEventDispatcher();

    return new ElasticsearchRequestWrapper($event_dispatcher, $operation, $callback, [$request_params], $this, $source);
  }

  /**
   * {@inheritdoc}
   */
  public function setup() {
    try {
      // Create an index if index definition is provided by the index plugin.
      if ($index_definition = $this->getIndexDefinition()) {
        $index_name = $this->getIndexName();

        if (!$this->client->indices()->exists(['index' => $index_name])->asBool()) {
          $this->createIndex($index_name, $index_definition);
        }
      }
    }
    catch (\Throwable $e) {
      $request_wrapper = isset($request_wrapper) ? $request_wrapper : NULL;
      $this->dispatchOperationErrorEvent($e, ElasticsearchOperations::INDEX_CREATE, $request_wrapper);
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
      $metadata = ['index_definition' => $index_definition];
      $operation_event = $this->dispatchOperationEvent($operation, $index_name, $metadata);

      if ($operation_event->isOperationAllowed()) {
        $callback = [$this->client->indices(), 'create'];
        $request_params = [
          'index' => $index_name,
          'body' => $index_definition->toArray(),
        ];

        // Create the index.
        $request_wrapper = $this->createRequest($operation, $callback, $request_params);
        $request_wrapper->execute();
      }
    }
    catch (\Throwable $e) {
      $request_wrapper = isset($request_wrapper) ? $request_wrapper : NULL;
      $this->dispatchOperationErrorEvent($e, $operation, $request_wrapper);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getExistingIndices() {
    try {
      $operation = ElasticsearchOperations::INDEX_GET;

      $callback = [$this->client->indices(), 'get'];
      $request_params = ['index' => $this->indexNamePattern()];

      // Get a list of indices.
      $request_wrapper = $this->createRequest($operation, $callback, $request_params);
      $request_result = $request_wrapper->execute();

      return array_keys($request_result->getResultBody()->asArray());
    }
    catch (\Throwable $e) {
      $request_wrapper = isset($request_wrapper) ? $request_wrapper : NULL;
      $this->dispatchOperationErrorEvent($e, $operation, $request_wrapper);

      throw $e;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function drop() {
    try {
      $operation = ElasticsearchOperations::INDEX_DROP;

      // Quietly get existing indices.
      try {
        $indices = $this->getExistingIndices();
      }
      catch (\Throwable $e) {
        $indices = [];
      }

      if ($indices) {
        $index = $this->indexNamePattern();
        $operation_event = $this->dispatchOperationEvent($operation, $index);

        if ($operation_event->isOperationAllowed()) {
          // Notify user that indices have been deleted.
          foreach ($indices as $index_name) {
            $this->messenger()->addStatus($this->t('Index @indexName is queued for removal.', ['@indexName' => $index_name]));
          }

          // Delete matching indices.
          $callback = [$this->client->indices(), 'delete'];
          $request_params = ['index' => implode(',', $indices)];

          $request_wrapper = $this->createRequest($operation, $callback, $request_params);
          $request_wrapper->execute();
        }
      }
    }
    catch (\Throwable $e) {
      $request_wrapper = isset($request_wrapper) ? $request_wrapper : NULL;
      $this->dispatchOperationErrorEvent($e, $operation, $request_wrapper);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function index($source) {
    try {
      $operation = ElasticsearchOperations::DOCUMENT_INDEX;
      $operation_event = $this->dispatchOperationEvent($operation, $source);

      if ($operation_event->isOperationAllowed()) {
        $source = $operation_event->getObject();
        $serialized_data = $this->serialize($source, ['method' => 'index']);

        $callback = [$this->client, 'index'];
        $request_params = [
          'index' => $this->getIndexName($serialized_data),
          'body' => $serialized_data,
        ];

        if ($id = $this->getId($serialized_data)) {
          $request_params['id'] = $id;
        }

        $request_wrapper = $this->createRequest($operation, $callback, $request_params, $source);
        $request_wrapper->execute();
      }
    }
    catch (\Throwable $e) {
      $request_wrapper = isset($request_wrapper) ? $request_wrapper : NULL;
      $this->dispatchOperationErrorEvent($e, $operation, $request_wrapper, $source);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function get($source) {
    try {
      $operation = ElasticsearchOperations::DOCUMENT_GET;
      $serialized_data = $this->serialize($source, ['method' => 'get']);

      $callback = [$this->client, 'get'];
      $request_params = [
        'index' => $this->getIndexName($serialized_data),
        'id' => $this->getId($serialized_data),
      ];

      $request_wrapper = $this->createRequest($operation, $callback, $request_params, $source);
      $request_result = $request_wrapper->execute();

      return $request_result->getResultBody()->asArray();
    }
    catch (\Throwable $e) {
      $request_wrapper = isset($request_wrapper) ? $request_wrapper : NULL;
      $this->dispatchOperationErrorEvent($e, $operation, $request_wrapper, $source);

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

      if ($operation_event->isOperationAllowed()) {
        $source = $operation_event->getObject();
        $serialized_data = $this->serialize($source, ['method' => 'upsert']);

        $callback = [$this->client, 'update'];
        $request_params = [
          'index' => $this->getIndexName($serialized_data),
          'id' => $this->getId($serialized_data),
          'body' => [
            'doc' => $serialized_data,
            'doc_as_upsert' => TRUE,
          ],
        ];

        $request_wrapper = $this->createRequest($operation, $callback, $request_params, $source);
        $request_wrapper->execute();
      }
    }
    catch (\Throwable $e) {
      $request_wrapper = isset($request_wrapper) ? $request_wrapper : NULL;
      $this->dispatchOperationErrorEvent($e, $operation, $request_wrapper, $source);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function delete($source) {
    try {
      $operation = ElasticsearchOperations::DOCUMENT_DELETE;
      $operation_event = $this->dispatchOperationEvent($operation, $source);

      if ($operation_event->isOperationAllowed()) {
        $source = $operation_event->getObject();
        $serialized_data = $this->serialize($source, ['method' => 'delete']);

        $callback = [$this->client, 'delete'];
        $request_params = [
          'index' => $this->getIndexName($serialized_data),
          'id' => $this->getId($serialized_data),
        ];

        $request_wrapper = $this->createRequest($operation, $callback, $request_params, $source);
        $request_wrapper->execute();
      }
    }
    catch (\Throwable $e) {
      $request_wrapper = isset($request_wrapper) ? $request_wrapper : NULL;
      $this->dispatchOperationErrorEvent($e, $operation, $request_wrapper, $source);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function search($params) {
    try {
      $operation = ElasticsearchOperations::QUERY_SEARCH;

      $callback = [$this->client, 'search'];
      $request_params = [
        'index' => $this->indexNamePattern(),
      ] + $params;

      $request_wrapper = $this->createRequest($operation, $callback, $request_params, $params);
      $request_result = $request_wrapper->execute();

      return $request_result->getResultBody()->asArray();
    }
    catch (\Throwable $e) {
      $request_wrapper = isset($request_wrapper) ? $request_wrapper : NULL;
      $this->dispatchOperationErrorEvent($e, $operation, $request_wrapper, $params);

      throw $e;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function msearch($params) {
    try {
      $operation = ElasticsearchOperations::QUERY_MULTI_SEARCH;

      $callback = [$this->client, 'msearch'];
      $request_params = [
        'index' => $this->indexNamePattern(),
      ] + $params;

      $request_wrapper = $this->createRequest($operation, $callback, $request_params, $params);
      $request_result = $request_wrapper->execute();

      return $request_result->getResultBody()->asArray();
    }
    catch (\Throwable $e) {
      $request_wrapper = isset($request_wrapper) ? $request_wrapper : NULL;
      $this->dispatchOperationErrorEvent($e, $operation, $request_wrapper, $params);

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

      if ($operation_event->isOperationAllowed()) {
        $body = $operation_event->getObject();
        $serialized_data = $this->serialize($body, ['method' => 'bulk']);

        $callback = [$this->client, 'bulk'];
        $request_params = [
          'index' => $this->getIndexName($serialized_data),
          'body' => $serialized_data,
        ];

        $request_wrapper = $this->createRequest($operation, $callback, $request_params, $body);
        $request_wrapper->execute();
      }
    }
    catch (\Throwable $e) {
      $request_wrapper = isset($request_wrapper) ? $request_wrapper : NULL;
      $this->dispatchOperationErrorEvent($e, $operation, $request_wrapper, $body);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function reindex(array $context = []) {
    if (isset($this->pluginDefinition['entityType'])) {
      try {
        $operation = ElasticsearchHelperEvents::REINDEX;
        $metadata = ['context' => $context];
        $operation_event = $this->dispatchOperationEvent($operation, NULL, $metadata);

        if ($operation_event->isOperationAllowed()) {
          $entity_type = $this->pluginDefinition['entityType'];
          $bundle = isset($this->pluginDefinition['bundle']) ? $this->pluginDefinition['bundle'] : NULL;

          $callback = [$this->getElasticsearchIndexPluginManager(), 'reindexEntities'];
          $params = [$entity_type, $bundle];

          $event = new ElasticsearchHelperCallbackEvent($operation, $callback, $params, $this);
          $this->getEventDispatcher()->dispatch($event, $operation);

          call_user_func_array($event->getCallback(), $event->getCallbackParameters());
        }
      }
      catch (\Throwable $e) {
        $this->dispatchOperationErrorEvent($e, $operation);
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function truncate() {
    try {
      $operation = ElasticsearchOperations::INDEX_TRUNCATE;

      // Quietly get existing indices.
      try {
        $indices = $this->getExistingIndices();
      }
      catch (\Throwable $e) {
        $indices = [];
      }

      if ($indices) {
        $index = $this->indexNamePattern();
        $operation_event = $this->dispatchOperationEvent($operation, $index);

        if ($operation_event->isOperationAllowed()) {
          // Truncate matching indices.
          $callback = [$this->client, 'deleteByQuery'];
          $request_params = [
            'index' => $index,
            'body' => ['query' => ['match_all' => new \stdClass()]]
          ];

          $request_wrapper = $this->createRequest($operation, $callback, $request_params);
          $request_wrapper->execute();

          // Notify user that indices have been truncated.
          foreach ($indices as $index_name) {
            $this->messenger()->addStatus($this->t('Index @indexName was truncated.', ['@indexName' => $index_name]));
          }
        }
      }
    }
    catch (\Throwable $e) {
      $request_wrapper = isset($request_wrapper) ? $request_wrapper : NULL;
      $this->dispatchOperationErrorEvent($e, $operation, $request_wrapper);
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
