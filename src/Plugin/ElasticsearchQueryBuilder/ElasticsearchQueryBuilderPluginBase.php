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
  public function getValuesFromView(ViewExecutable $view) {
    $values = [];
    /** @var \Drupal\views\Plugin\views\filter\FilterPluginBase $filter */
    foreach ($view->filter as $field_name => $filter) {
      $values[$field_name] = $filter->value;
    }
    return $values;
  }

}
