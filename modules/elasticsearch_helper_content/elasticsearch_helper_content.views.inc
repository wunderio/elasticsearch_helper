<?php

/**
 * @file
 * Provide views data for Elasticsearch indices.
 */

/**
 * Implements hook_views_data().
 */
function elasticsearch_helper_content_views_data() {
  $class = '\Drupal\elasticsearch_helper_content\ElasticsearchContentIndexViewsData';
  /** @var \Drupal\views\EntityViewsDataInterface $instance */
  $instance = \Drupal::service('class_resolver')->getInstanceFromDefinition($class);

  return $instance->getViewsData();
}
