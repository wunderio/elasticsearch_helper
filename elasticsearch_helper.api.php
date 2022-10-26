<?php

/**
 * @file
 * Hooks provided by the elasticsearch_helper module.
 */

use Drupal\Core\Entity\Query\QueryInterface;
use Elasticsearch\ClientBuilder;

/**
 * @addtogroup hooks
 * @{
 */

/**
 * Configure the Elasticsearch Client object.
 *
 * @param \Elastic\Elasticsearch\ClientBuilder
 *   The ClientBuilder object.
 */
function hook_elasticsearch_helper_client_builder_alter(ClientBuilder $clientBuilder) {
  // Send log entries from the client directly to Drupal's log.
  $clientBuilder->setLogger(\Drupal::logger('elasticsearch'));
}

/**
 * Alters the entity query which selects entities for reindexing.
 *
 * @param \Drupal\Core\Entity\Query\QueryInterface $query
 *   Entity query instance.
 * @param $entity_type
 *   Type of entities which need to be re-indexed.
 * @param string|null $bundle
 *   Optional bundle name.
 */
function hook_elasticsearch_helper_reindex_entity_query_alter(QueryInterface $query, $entity_type, $bundle = NULL) {
  // Do not restrict entity query based on user's permissions or node grants.
  $query->accessCheck(FALSE);
}

/**
 * @} End of "addtogroup hooks".
 */
