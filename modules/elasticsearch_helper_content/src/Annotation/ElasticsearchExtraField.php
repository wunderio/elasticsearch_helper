<?php

namespace Drupal\elasticsearch_helper_content\Annotation;

use Drupal\Component\Annotation\Plugin;

/**
 * Defines a Elasticsearch extra field item annotation object.
 *
 * @see \Drupal\elasticsearch_helper_content\ElasticsearchExtraFieldManager
 * @see plugin_api
 *
 * @Annotation
 */
class ElasticsearchExtraField extends Plugin {

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
