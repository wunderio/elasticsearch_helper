<?php

namespace Drupal\elasticsearch_helper_views\Plugin\ElasticsearchQueryBuilder;

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
  public function getFilterValues(ViewExecutable $view) {
    $values = [];
    /** @var \Drupal\views\Plugin\views\filter\FilterPluginBase $filter */
    foreach ($view->filter as $filter) {
      $values[$filter->realField] = $filter->value;
    }
    return $values;
  }

  /**
   * {@inheritdoc}
   */
  public function getArgumentValues(ViewExecutable $view) {
    $arguments = [];
    /** @var \Drupal\views\Plugin\views\argument\ArgumentPluginBase $argument */
    foreach ($view->argument as $argument) {
      $arguments[$argument->realField] = $argument->getValue();
    }
    return $arguments;
  }

  /**
   * {@inheritdoc}
   */
  public function getSortValues(ViewExecutable $view) {
    $arguments = [];
    /** @var \Drupal\views\Plugin\views\sort\SortPluginBase $sort */
    foreach ($view->sort as $sort) {
      $arguments[$sort->realField] = strtolower($sort->options['order']);
    }
    return $arguments;
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
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheTags() {
    return [];
  }

}
