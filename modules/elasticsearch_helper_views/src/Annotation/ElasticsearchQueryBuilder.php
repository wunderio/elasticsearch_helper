<?php

namespace Drupal\elasticsearch_helper_views\Annotation;

use Drupal\Component\Annotation\Plugin;

/**
 * Defines an Elasticsearch query builder annotation object.
 *
 * @see \Drupal\elasticsearch_helper_views\Plugin\ElasticsearchQueryBuilderManager
 * @see plugin_api
 *
 * @Annotation
 */
class ElasticsearchQueryBuilder extends Plugin {

  /**
   * The plugin ID.
   *
   * @var string
   */
  public $id;

  /**
   * The label of the plugin.
   *
   * @var \Drupal\Core\Annotation\Translation
   *
   * @ingroup plugin_translatable
   */
  public $label;

  /**
   * The description of the plugin.
   *
   * @var \Drupal\Core\Annotation\Translation
   *
   * @ingroup plugin_translatable
   */
  public $description;

}
