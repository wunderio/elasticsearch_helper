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
function elasticsearch_helper_aws_elasticsearch_helper_client_builder_alter(\Elasticsearch\ClientBuilder $clientBuilder) {
  // Send log entries from the client directly to Drupal's log.
  $clientBuilder->setLogger(\Drupal::logger('elasticsearch'));
}

/**
 * @} End of "addtogroup hooks".
 */
