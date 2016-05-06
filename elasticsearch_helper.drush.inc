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
