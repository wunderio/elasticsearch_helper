<?php

namespace Drupal\elasticsearch_helper\EventSubscriber;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\elasticsearch_helper\Event\ElasticsearchEvents;
use Drupal\elasticsearch_helper\Event\ElasticsearchHelperEntityOperationEvent;
use Drupal\elasticsearch_helper\Event\ElasticsearchHelperEvents;
use Drupal\elasticsearch_helper\Event\ElasticsearchOperationEvent;
use Drupal\elasticsearch_helper\Event\ElasticsearchOperations;
use Drupal\elasticsearch_helper\Event\ElasticsearchSerializeEvent;
use Drupal\elasticsearch_helper\ElasticsearchHelperCache;
use Drupal\elasticsearch_helper\Plugin\ElasticsearchIndexManager;
use Symfony\Component\EventDispatcher\Event;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Caches the serialized content.
 */
class EntitySerializationCacheEventSubscriber implements EventSubscriberInterface {

  /**
   * Cache handler.
   *
   * @var \Drupal\elasticsearch_helper\ElasticsearchHelperCache
   */
  protected $elasticsearchHelperCache;

  /**
   * Elasticsearch Helper config entity.
   *
   * @var \Drupal\Core\Config\ImmutableConfig
   */
  protected $config;

  /**
   * Elasticsearch index manager.
   *
   * @var \Drupal\elasticsearch_helper\Plugin\ElasticsearchIndexManager
   */
  protected $elasticsearchIndexManager;

  /**
   * EntitySerializationCacheEventSubscriber constructor.
   *
   * @param \Drupal\elasticsearch_helper\ElasticsearchHelperCache $serialization_cache
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   * @param \Drupal\elasticsearch_helper\Plugin\ElasticsearchIndexManager $elasticsearch_index_manager
   */
  public function __construct(ElasticsearchHelperCache $serialization_cache, ConfigFactoryInterface $config_factory, ElasticsearchIndexManager $elasticsearch_index_manager) {
    $this->elasticsearchHelperCache = $serialization_cache;
    $this->config = $config_factory->get('elasticsearch_helper.settings');
    $this->elasticsearchIndexManager = $elasticsearch_index_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    $events = [];
    $events[ElasticsearchEvents::OPERATION][] = ['onDocumentDelete'];
    $events[ElasticsearchHelperEvents::ENTITY_INSERT][] = ['onEntityInsert'];
    $events[ElasticsearchHelperEvents::PRE_SERIALIZE][] = ['onPreSerialize'];
    $events[ElasticsearchHelperEvents::POST_SERIALIZE][] = ['onPostSerialize'];

    return $events;
  }

  /**
   * Invalidates or clears entity serialization cache.
   *
   * @param \Drupal\elasticsearch_helper\Event\ElasticsearchHelperEntityOperationEvent $event
   */
  public function onEntityInsert(ElasticsearchHelperEntityOperationEvent $event) {
    $entity = $event->getEntity();

    if ($this->config->get('defer_indexing')) {
      // Invalidate the serialized data cache.
      $this->elasticsearchHelperCache->invalidateEntityCache($entity);
    }
    else {
      $this->elasticsearchIndexManager->clearEntityCache($entity);
    }
  }

  /**
   * Clears serialization cache on Elasticsearch document removal.
   *
   * @param \Drupal\elasticsearch_helper\Event\ElasticsearchOperationEvent $event
   */
  public function onDocumentDelete(ElasticsearchOperationEvent $event) {
    $operation = $event->getOperation();

    if ($operation == ElasticsearchOperations::DOCUMENT_DELETE) {
      $object = $event->getObject();

      if ($object instanceof EntityInterface) {
        if ($this->isSerializationCacheEnabled($event)) {
          $plugin_instance = $event->getPluginInstance();

          // Clear entity serialization cache.
          $this->elasticsearchHelperCache->clearEntitySerializationCache($object, $plugin_instance->getPluginId());
        }
      }
    }
  }

  /**
   * Checks if serialized content is stored in cache.
   *
   * @param \Drupal\elasticsearch_helper\Event\ElasticsearchSerializeEvent $event
   */
  public function onPreSerialize(ElasticsearchSerializeEvent $event) {
    $object = $event->getObject();

    if ($object instanceof EntityInterface) {
      $context = $event->getContext();

      // Load a cached version of the data if it exists.
      // Cache should only be used when indexing content.
      if ($this->isSerializationCacheEnabled($event) && $context['method'] == 'index') {
        $plugin_instance = $event->getPluginInstance();

        // Get cache object.
        $cache = $this->elasticsearchHelperCache->getEntitySerializationCache($object, $plugin_instance->getPluginId());

        // Put cached serialized data back into the event
        // only if cache result not FALSE.
        if ($cache !== FALSE) {
          $serialized_data = &$event->serializedData();
          $serialized_data = $cache->data;
        }
      }
    }
  }

  /**
   * Stores serialized entity data in cache.
   *
   * @param \Drupal\elasticsearch_helper\Event\ElasticsearchSerializeEvent $event
   */
  public function onPostSerialize(ElasticsearchSerializeEvent $event) {
    $object = $event->getObject();

    if ($object instanceof EntityInterface) {
      $context = $event->getContext();

      // Store data in cache after serialization.
      if ($this->isSerializationCacheEnabled($event) && $context['method'] == 'index') {
        $plugin_instance = $event->getPluginInstance();
        // Get serialized data.
        $serialized_data = $event->serializedData();

        // Set serialized entity data cache.
        $this->elasticsearchHelperCache->setEntitySerializationCache($serialized_data, $object, $plugin_instance->getPluginId());
      }
    }
  }

  /**
   * Returns TRUE if serialization cache is enabled.
   *
   * @param \Drupal\elasticsearch_helper\Event\ElasticsearchOperationEvent|\Drupal\elasticsearch_helper\Event\ElasticsearchSerializeEvent $event
   *
   * @return bool
   */
  protected function isSerializationCacheEnabled(Event $event) {
    $plugin_instance = $event->getPluginInstance();
    $plugin_definition = $plugin_instance->getPluginDefinition();

    return (bool) ($plugin_definition['serializationCache'] ?? FALSE);
  }

}
