<?php

namespace Drupal\elasticsearch_helper_content;

use Drupal\Component\Plugin\PluginBase;

/**
 * Base class for Elasticsearch Content Normalizer plugins.
 */
abstract class ElasticsearchNormalizerBase extends PluginBase implements ElasticsearchNormalizerInterface {

  /**
   * {@inheritdoc}
   */
  public function normalize($object, array $context = []) {
    return '';
  }

}
