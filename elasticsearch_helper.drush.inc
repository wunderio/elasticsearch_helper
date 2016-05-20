<?php

use Drupal\elasticsearch_helper\Plugin\ElasticsearchIndexManager;

/**
 * Implements hook_drush_command().
 */
function elasticsearch_helper_drush_command() {
  $items = [];

  $items['elasticsearch-helper-list'] =  [
    'description' => 'List ElasticsearchIndex plugins.',
    'aliases' => ['eshl'],
  ];

  $items['elasticsearch-helper-setup'] =  [
    'description' => 'Setup Elasticsearch indices.',
    'arguments' => [
      'indices' => 'Comma separated list of indices to be set up'
    ],
    'aliases' => ['eshs'],
  ];

  $items['elasticsearch-helper-drop'] =  [
    'description' => 'Drop Elasticsearch indices.',
    'arguments' => [
      'indices' => 'Comma separated list of indices to be deleted'
    ],
    'aliases' => ['eshd'],
  ];

  return $items;
}

function drush_elasticsearch_helper_list() {
  /** @var ElasticsearchIndexManager $elasticsearchPluginManager */
  $elasticsearchPluginManager = \Drupal::service('plugin.manager.elasticsearch_index.processor');

  $header = ['id', 'Name'];

  $rows = [];
  foreach ($elasticsearchPluginManager->getDefinitions() as $plugin) {
    $rows[] = [
      $plugin['id'],
      $plugin['label']
    ];
  }

  drush_print_table($rows, $header);
}

function drush_elasticsearch_helper_setup($indices = FALSE) {

  // Indices can be specified with a comma-separate value.
  if ($indices && is_string($indices)) {
    $indices = explode(',', $indices);
  }

  /** @var ElasticsearchIndexManager $elasticsearchPluginManager */
  $elasticsearchPluginManager = \Drupal::service('plugin.manager.elasticsearch_index.processor');

  foreach ($elasticsearchPluginManager->getDefinitions() as $plugin) {
    if (!$indices || in_array($plugin['id'], $indices)) {
      $elasticsearchPluginManager->createInstance($plugin['id'])->setup();
    }
  }
}

function drush_elasticsearch_helper_drop($indices = FALSE) {
  // Indices can be specified with a comma-separate value.
  if ($indices && is_string($indices)) {
    $indices = explode(',', $indices);
  }

  /** @var ElasticsearchIndexManager $elasticsearchPluginManager */
  $elasticsearchPluginManager = \Drupal::service('plugin.manager.elasticsearch_index.processor');

  foreach ($elasticsearchPluginManager->getDefinitions() as $plugin) {
    if (!$indices || in_array($plugin['id'], $indices)) {

      $pluginInstance = $elasticsearchPluginManager->createInstance($plugin['id']);

      $rows = [];
      foreach ($pluginInstance->getExistingIndices() as $index) {
        $rows[] = [$index];
      }

      if (count($rows)) {
        drush_print('The following indices exist in elasticsearch:');
        drush_print_table($rows);
        if (drush_confirm(dt('Are you sure you want to delete them?'))) {
          $pluginInstance->drop();
        }
      }
      else {
        drush_print('There are no indices to be deleted.');
      }
    }
  }
}
