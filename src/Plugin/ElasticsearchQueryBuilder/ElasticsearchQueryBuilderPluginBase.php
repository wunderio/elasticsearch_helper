<?php

namespace Drupal\elasticsearch_helper_views\Plugin\ElasticsearchQueryBuilder;

use Drupal\Core\Cache\Cache;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\elasticsearch_helper_views\ElasticsearchQueryBuilderInterface;
use Drupal\views\Plugin\views\display\DisplayPluginBase;
use Drupal\views\Plugin\views\PluginBase;
use Drupal\views\ViewExecutable;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Base class for Elasticsearch index plugins.
 */
abstract class ElasticsearchQueryBuilderPluginBase extends PluginBase implements ElasticsearchQueryBuilderInterface, ContainerFactoryPluginInterface {

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition
    );
  }

  /**
   * {@inheritdoc}
   */
  public function init(ViewExecutable $view, DisplayPluginBase $display, array &$options = NULL) {
    parent::init($view, $display, $options);
  }

  /**
   * {@inheritdoc}
   */
  public function getFilterValues() {
    $values = [];

    if (!empty($this->view->filter)) {
      /** @var \Drupal\views\Plugin\views\filter\FilterPluginBase $filter */
      foreach ($this->view->filter as $filter) {
        $values[$filter->realField] = $filter->value;
      }
    }

    return $values;
  }

  /**
   * {@inheritdoc}
   */
  public function getArgumentValues() {
    $values = [];

    if (!empty($this->view->argument)) {
      /** @var \Drupal\views\Plugin\views\argument\ArgumentPluginBase $argument */
      foreach ($this->view->argument as $argument) {
        $values[$argument->realField] = $argument->getValue();
      }
    }

    return $values;
  }

  /**
   * {@inheritdoc}
   */
  public function getSortValues() {
    $values = [];

    if (!empty($this->view->sort)) {
      /** @var \Drupal\views\Plugin\views\sort\SortPluginBase $sort */
      foreach ($this->view->sort as $sort) {
        $values[$sort->realField] = strtolower($sort->options['order']);
      }
    }

    return $values;
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheMaxAge() {
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheContexts() {
    $contexts = [];

    // Merge in cache contexts for all exposed filters.
    foreach ($this->displayHandler->getHandlers('filter') as $filter_handler) {
      /** @var \Drupal\views\Plugin\views\Filter\FilterPluginBase $filter_handler */
      if ($filter_handler->isExposed()) {
        $contexts = Cache::mergeContexts($contexts, $filter_handler->getCacheContexts());
      }
    }

    return $contexts;
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheTags() {
    return [];
  }

}
