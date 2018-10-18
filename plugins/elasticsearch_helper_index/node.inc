<?php

$plugin = [
  "id" => "content_index_node",
  "label" => t("Node Index (Multilingual)"),
  "indexName" => "content-node-{langcode}",
  "typeName" => "node",
  "entityType" => "node",
  "index_callback" => 'elasticsearch_helper_index_node',
  "delete_callback" => 'elasticsearch_helper_delete_node',
  "serialize_callback" => 'elasticsearch_helper_serialize_node'
];

function elasticsearch_helper_index_node($node, $plugin) {
  // Beware: $plugin may have been altered!
  if (isset($plugin['serialize_callback']) && is_callable($plugin['serialize_callback'])) {
    $serialized_data = $plugin['serialize_callback']($node);
  }
  else {
    throw new Exception("Elasticsearch Helper: I don't know how to serizalize this node.");
  }
  $client = elasticsearch_helper_get_client();

  $params = [
    'index' => $plugin['indexName'],
    'type' => $plugin['typeName'],
    'body' => $serialized_data,
  ];

  if ($id = _elasticsearch_helper_get_id($serialized_data)) {
    $params['id'] = $id;
  }

  $client->index($params);
}

function elasticsearch_helper_serialize_node($node) {
  // That's trivial serialization right here.
  return json_encode($node);
}