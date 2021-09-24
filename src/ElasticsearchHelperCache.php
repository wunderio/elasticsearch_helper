<?php

namespace Drupal\elasticsearch_helper;

use Drupal\Core\Cache\Cache;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Entity\EntityInterface;

/**
 * Cache handler.
 */
class ElasticsearchHelperCache {

  /**
   * Entity serialization cache prefix.
   *
   * @var string
   */
  const CID_PREFIX = 'elasticsearch_helper_cache';

  /**
   * Cache instance.
   *
   * @var \Drupal\Core\Cache\CacheBackendInterface
   */
  protected $cache;

  /**
   * SerializationCacheHandler constructor.
   *
   * @param \Drupal\Core\Cache\CacheBackendInterface $cache
   */
  public function __construct(CacheBackendInterface $cache) {
    $this->cache = $cache;
  }

  /**
   * Returns cache ID for the entity.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   * @param string $plugin_id
   *
   * @return string
   */
  public function getEntitySerializationCacheId(EntityInterface $entity, $plugin_id) {
    return static::CID_PREFIX . ':entity_serialization:' . $plugin_id . ':' . $entity->getEntityTypeId() . ':' . $entity->language()->getId() . ':' . $entity->id();
  }

  /**
   * Returns serialization cache tags.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *
   * @return string[]
   */
  public function getEntitySerializationCacheTags(EntityInterface $entity) {
    return [
      static::CID_PREFIX . ':entity_serialization',
      static::CID_PREFIX . ':entity_serialization:' . $entity->getEntityTypeId() . ':' . $entity->id(),
      $entity->getEntityTypeId() . ':' . $entity->id(),
    ];
  }

  /**
   * Returns cached serialized entity data.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   * @param string $plugin_id
   *
   * @return object|false
   */
  public function getEntitySerializationCache(EntityInterface $entity, $plugin_id) {
    // Get the cache ID.
    $cache_id = $this->getEntitySerializationCacheId($entity, $plugin_id);
    // Get cached serialized data.
    return $this->cache->get($cache_id);
  }

  /**
   * Sets serialized entity data in cache.
   *
   * @param mixed $data
   * @param \Drupal\Core\Entity\EntityInterface $entity
   * @param string $plugin_id
   */
  public function setEntitySerializationCache($data, EntityInterface $entity, $plugin_id) {
    // Get cache ID for given object.
    $cache_id = $this->getEntitySerializationCacheId($entity, $plugin_id);
    $tags = $this->getEntitySerializationCacheTags($entity);

    $this->cache->set($cache_id, $data, Cache::PERMANENT, $tags);
  }

  /**
   * Invalidates entity serialization cache.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   */
  public function invalidateEntityCache(EntityInterface $entity) {
    Cache::invalidateTags([static::CID_PREFIX . ':entity_serialization:' . $entity->getEntityTypeId() . ':' . $entity->id()]);
  }

  /**
   * Clears serialized entity data cache.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   * @param string $plugin_id
   *
   * @return mixed
   */
  public function clearEntitySerializationCache(EntityInterface $entity, $plugin_id) {
    // Get the cache ID.
    $cache_id = $this->getEntitySerializationCacheId($entity, $plugin_id);
    // Clear cached serialized data.
    return $this->cache->delete($cache_id);
  }

}
