<?php

namespace Drupal\elasticsearch_helper\Annotation;

use Drupal\Component\Annotation\Plugin;

/**
 * Defines a Elasticsearch index item annotation object.
 *
 * @see \Drupal\elasticsearch_helper\Plugin\ElasticsearchIndexManager
 * @see plugin_api
 *
 * @Annotation
 */
class ElasticsearchIndex extends Plugin {

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

}
