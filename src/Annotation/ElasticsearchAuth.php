<?php

namespace Drupal\elasticsearch_helper\Annotation;

use Drupal\Component\Annotation\Plugin;

/**
 * Defines an Elasticsearch auth annotation object.
 *
 * @Annotation
 */
class ElasticsearchAuth extends Plugin {

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
  public $title;

  /**
   * The description of the plugin.
   *
   * @var \Drupal\Core\Annotation\Translation
   *
   * @ingroup plugin_translatable
   */
  public $description;

  /**
   * The weight of the plugin.
   *
   * @var numeric
   */
  public $weight;

}
