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

  $items['elasticsearch-helper-drop'] = [
    'description' => 'Drop Elasticsearch indices.',
    'arguments' => [
      'indices' => 'Comma separated list of indices to be deleted',
    ],
    'aliases' => ['eshd'],
  ];

  $items['elasticsearch-helper-reindex'] = [
    'description' => 'Reindex entities associated with an elasticsearch index',
    'arguments' => [
      'indices' => 'Comma separated list of indices for which entities should be reindexed',
    ],
    'aliases' => ['eshr'],
  ];

  return $items;
}

/**
 * List ElasticsearchIndex plugins
 */
function drush_elasticsearch_helper_list() {
  $rows[] = ['id', 'Name'];
  foreach (elasticsearch_helper_get_index_processor()->getDefinitions() as $plugin) {
    $rows[] = [
      $plugin['id'],
      $plugin['label'],
    ];
  }

  drush_print_table($rows, TRUE);
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
          $function($plugin);
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

/**
 * Drop Elasticsearch indices.
 */
function drush_elasticsearch_helper_drop($indices = FALSE) {
  // Indices can be specified with a comma-separate value.
  if ($indices && is_string($indices)) {
    $indices = explode(',', $indices);
  }

  foreach (elasticsearch_helper_get_index_processor()->getDefinitions() as $plugin) {
    if (!$indices || in_array($plugin['id'], $indices)) {
      try {
        if ($function = ctools_plugin_get_function($plugin, 'drop_callback')) {
          $function($plugin);
        }
      }
      catch (Exception $e) {
        watchdog('Elasticsearch drop failed: @message', [
          '@message' => $e->getMessage(),
        ], WATCHDOG_ERROR);
      }
    }
  }
}

/**
 * Reindex entities associated with an elasticsearch index.
 */
function drush_elasticsearch_helper_reindex($indices = FALSE) {
  // Indices can be specified with a comma-separate value.
  if ($indices && is_string($indices)) {
    $indices = explode(',', $indices);
  }

  foreach (elasticsearch_helper_get_index_processor()->getDefinitions() as $plugin) {
    if (!$indices || in_array($plugin['id'], $indices)) {
      if ($plugin['entityType']) {
        $query = new EntityFieldQuery();
        $query->entityCondition('entity_type', $plugin['entityType']);

        if ($plugin['bundle']) {
          $query->entityCondition('bundle', $plugin['bundle']);
        }
        $result = $query->execute();

        $entity_info = entity_get_info($plugin['entityType']);
        foreach ($result[$plugin['entityType']] as $entity) {
          DrupalQueue::get('elasticsearch_helper_indexing')
            ->createItem([
              'entity_type' => $plugin['entityType'],
              'entity_id' => $entity->{$entity_info['entity keys']['id']},
            ]);
        }

        drupal_set_message('Marked indices to be reindex on next cronrun');
      }
    }
  }

}
