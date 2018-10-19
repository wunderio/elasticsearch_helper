<?php

use Elasticsearch\Common\Exceptions\Missing404Exception;

// The default callback implementations reside here:
require drupal_get_path('module', 'elasticsearch_helper') . "/elasticsearch_helper.callback.inc";

$plugin = [
  "id" => "content_index_node",
  "label" => t("Node Index"),
  "indexName" => "content-node",
  "typeName" => "node",
  "entityType" => "node",
  "index_callback" => 'elasticsearch_helper_node_index',
  "delete_callback" => 'elasticsearch_helper_node_delete',
  "serialize_callback" => 'elasticsearch_helper_node_serialize',
  'setup_callback' => 'elasticsearch_helper_node_setup',
];
