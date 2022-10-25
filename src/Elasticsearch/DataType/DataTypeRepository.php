<?php

namespace Drupal\elasticsearch_helper\Elasticsearch\DataType;

use Drupal\Core\Cache\Cache;
use Drupal\Core\Cache\CacheableDependencyTrait;
use Drupal\Core\Cache\UseCacheBackendTrait;
use Drupal\elasticsearch_helper\Event\DataTypeDefinitionBuildEvent;
use Drupal\elasticsearch_helper\Event\DataTypeEvents;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * Class DataTypeRepository
 */
class DataTypeRepository implements DataTypeRepositoryInterface {

  use UseCacheBackendTrait;
  use CacheableDependencyTrait;

  /**
   * @var \Symfony\Component\EventDispatcher\EventDispatcherInterface
   */
  protected $eventDispatcher;

  /**
   * @var string
   */
  protected $cacheId = 'elasticsearch_helper:data_type';

  /**
   * @var array
   */
  protected $dataTypeDefinitions = [
    // Text data types.
    'text' => [],
    'keyword' => [],
    'wildcard' => [],
    // Numeric data types.
    'long' => [],
    'integer' => [],
    'short' => [],
    'byte' => [],
    'double' => [],
    'float' => [],
    'half_float' => [],
    'scaled_float' => [],
    // Date data types.
    'date' => [],
    // Boolean data types.
    'boolean' => [],
    // Boolean data types.
    'binary' => [],
    // Range data types.
    'integer_range' => [],
    'float_range' => [],
    'long_range' => [],
    'double_range' => [],
    'date_range' => [],
    // Geo-point data types.
    'geo_point' => [],
    // Geo-shape data types.
    'geo_shape' => [],
    // IP data types.
    'ip' => [],
    // Complex data types.
    'object' => [],
    'nested' => [],
  ];

  /**
   * @var array
   */
  protected $staticDataTypeDefinitions;

  /**
   * DataTypeRepository constructor.
   *
   * @param \Symfony\Component\EventDispatcher\EventDispatcherInterface $event_dispatcher
   */
  public function __construct(EventDispatcherInterface $event_dispatcher) {
    $this->eventDispatcher = $event_dispatcher;
  }

  /**
   * {@inheritdoc}
   */
  public function getTypeDefinitions() {
    if (is_null($this->staticDataTypeDefinitions)) {
      // Attempt to get data type definitions from cache.
      $cache = $this->cacheGet($this->cacheId);

      if ($cache) {
        $this->staticDataTypeDefinitions = $cache->data;
      }
      else {
        // Get default definitions.
        $definitions = $this->dataTypeDefinitions;

        // Allow modules to alter the definitions.
        $event = new DataTypeDefinitionBuildEvent($definitions);
        $this->eventDispatcher->dispatch($event, DataTypeEvents::BUILD);

        // Store in cache.
        $this->cacheSet($this->cacheId, $event->getDataTypeDefinitions(), Cache::PERMANENT, $this->getCacheTags());

        // Store definitions statically.
        $this->staticDataTypeDefinitions = $definitions;
      }
    }

    return $this->staticDataTypeDefinitions;
  }

  /**
   * {@inheritdoc}
   */
  public function getTypeDefinition($type) {
    $definitions = $this->getTypeDefinitions();

    if (isset($definitions[$type])) {
      return $definitions[$type];
    }

    return [];
  }

}
