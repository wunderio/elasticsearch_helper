<?php

namespace Drupal\elasticsearch_helper_views\Plugin\ElasticsearchQueryBuilder;

use Drupal\Component\Plugin\PluginBase;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\elasticsearch_helper_views\ElasticsearchQueryBuilderInterface;
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
  public function getFilterValues(ViewExecutable $view) {
    $values = [];
    /** @var \Drupal\views\Plugin\views\filter\FilterPluginBase $filter */
    foreach ($view->filter as $name => $filter) {
      $values[$name] = $filter->value;
    }
    return $values;
  }

  /**
   * {@inheritdoc}
   */
  public function getArgumentValues(ViewExecutable $view) {
    $arguments = [];
    /** @var \Drupal\views\Plugin\views\argument\ArgumentPluginBase $argument */
    foreach ($view->argument as $name => $argument) {
      $arguments[$name] = $argument->getValue();
    }
    return $arguments;
  }

}
