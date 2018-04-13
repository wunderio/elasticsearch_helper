<?php

namespace Drupal\elasticsearch_helper_views;

use Drupal\Core\Plugin\DefaultPluginManager;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;

/**
 * Provides the Elasticsearch query builder plugin manager.
 */
class ElasticsearchQueryBuilderManager extends DefaultPluginManager {

  public function __construct(\Traversable $namespaces, CacheBackendInterface $cache_backend, ModuleHandlerInterface $module_handler) {
    parent::__construct('Plugin/ElasticsearchQueryBuilder', $namespaces, $module_handler, 'Drupal\elasticsearch_helper_views\ElasticsearchQueryBuilderInterface', 'Drupal\elasticsearch_helper_views\Annotation\ElasticsearchQueryBuilder');

    $this->alterInfo('elasticsearch_helper_views_elasticsearch_query_builder_info');
    $this->setCacheBackend($cache_backend, 'elasticsearch_helper_views_elasticsearch_query_builder_plugins');
  }

}
