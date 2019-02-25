<?php

namespace Drupal\elasticsearch_helper_content\Annotation;

use Drupal\Component\Annotation\Plugin;

/**
 * Defines a Elasticsearch entity normalizer item annotation object.
 *
 * @see \Drupal\elasticsearch_helper_content\ElasticsearchEntityNormalizerManager
 * @see plugin_api
 *
 * @Annotation
 */
class ElasticsearchEntityNormalizer extends Plugin {

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
