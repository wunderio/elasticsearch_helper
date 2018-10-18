<?php

/**
 * @file
 * Hooks provided by the elasticsearch_helper module.
 */

/**
 * @addtogroup hooks
 * @{
 */

/**
 * Configure the Elasticsearch Client object.
 *
 * @param \Elasticsearch\ClientBuilder
 *   The ClientBuilder object.
 */
function hook_elasticsearch_helper_client_builder_alter($clientBuilder) {
  // Send log entries from the client directly to Drupal's log.
  $clientBuilder->setLogger(\Drupal::logger('elasticsearch'));
}

/**
 * Allow altering a plugins after loading
 *
 * @param array plugin definition
 * @param array plugin type info
 */
function hook_elasticsearch_helper_index_plugin_alter(&$plugin, &$info) {
  if ($plugin['id'] == 'content_index_node') {
    $plugin['serialize_callback'] = "my_custom_seralization_callback";
  }
}

/**
 * @} End of "addtogroup hooks".
 */
