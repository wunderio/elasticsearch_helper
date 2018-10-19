<?php

use Elasticsearch\Common\Exceptions\Missing404Exception;

/**
 * Index callback for use with an elasticsearch_helper_index plugin.
 */
function elasticsearch_helper_node_index($node, $plugin) {
  // Beware: $plugin may have been altered!
  if (isset($plugin['serialize_callback']) && is_callable($plugin['serialize_callback'])) {
    $serialized_data = $plugin['serialize_callback']($node);
  }
  else {
    throw new Exception("Elasticsearch Helper: I don't know how to serialize this node.");
  }

  $params = [
    'index' => $plugin['indexName'],
    'type' => $plugin['typeName'],
    'body' => $serialized_data,
  ];

  if ($id = _elasticsearch_helper_get_id($serialized_data)) {
    $params['id'] = $id;
  }

  elasticsearch_helper_get_client()->index($params);
}

/**
 * Delete callback for use with an elasticsearch_helper_index plugin.
 */
function elasticsearch_helper_node_delete($node, $plugin) {
  // Beware: $plugin may have been altered!
  if (isset($plugin['serialize_callback']) && is_callable($plugin['serialize_callback'])) {
    $serialized_data = $plugin['serialize_callback']($node);
  }
  else {
    throw new Exception("Elasticsearch Helper: I don't know how to serialize this node.");
  }

  $id = _elasticsearch_helper_get_id($serialized_data);

  $params = [
    'index' => $plugin['indexName'],
    'type' => $plugin['typeName'],
    'id' => $id,
  ];

  try {
    elasticsearch_helper_get_client()->delete($params);
  }
  catch (Missing404Exception $e) {
    watchdog('Could not delete entry with id @id from elasticsearch index', ['@id' => $params['id'],]);
  }
}

/**
 * Index serialize for use with an elasticsearch_helper_index plugin.
 */
function elasticsearch_helper_node_serialize($node) {
  // That's trivial serialization right there.
  return json_decode(json_encode($node), true);
}

/**
 * Serialize callback for use with an elasticsearch_helper_index plugin.
 */
function elasticsearch_helper_node_setup() {
  print __FUNCTION__ . "\n";
  // TODO: create index templates.
  // elasticsearch_helper_get_client()->indices()->putTemplate().
}