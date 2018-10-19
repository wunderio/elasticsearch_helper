<?php

/**
 * @file
 *
 * Drush commands for the elasticsearch_helper module.
 */

/**
 * Implements hook_drush_command().
 */
function elasticsearch_helper_drush_command() {
  $items = [];

  $items['elasticsearch-helper-list'] = [
    'description' => 'List ElasticsearchIndex plugins.',
    'aliases' => ['eshl'],
  ];

  $items['elasticsearch-helper-setup'] = [
    'description' => 'Setup Elasticsearch indices.',
    'arguments' => [
      'indices' => 'Comma separated list of indices to be set up',
    ],
    'aliases' => ['eshs'],
  ];

  return $items;
}

/**
 * List ElasticsearchIndex plugins
 */
function drush_elasticsearch_helper_list() {
  $header = ['id', 'Name'];
  $rows = [];
  foreach (elasticsearch_helper_get_index_processor()->getDefinitions() as $plugin) {
    $rows[] = [
      $plugin['id'],
      $plugin['label'],
    ];
  }

  drush_print_table($rows, $header);
}

/**
 * Setup Elasticsearch indices.
 */
function drush_elasticsearch_helper_setup($indices = FALSE) {
  // Indices can be specified with a comma-separate value.
  if ($indices && is_string($indices)) {
    $indices = explode(',', $indices);
  }

  foreach (elasticsearch_helper_get_index_processor()->getDefinitions() as $plugin) {
    if (!$indices || in_array($plugin['id'], $indices)) {
      try {
        if ($function = ctools_plugin_get_function($plugin, 'setup_callback')) {
          $function($entity, $plugin);
        }

      }
      catch (Exception $e) {
      watchdog('Elasticsearch setup failed: @message', [
          '@message' => $e->getMessage(),
        ], WATCHDOG_ERROR);
      }
    }
  }
}