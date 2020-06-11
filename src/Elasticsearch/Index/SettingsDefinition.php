<?php

namespace Drupal\elasticsearch_helper\Elasticsearch\Index;

use Drupal\elasticsearch_helper\Elasticsearch\DefinitionBase;
use Drupal\elasticsearch_helper\Elasticsearch\ObjectTrait;

/**
 * Elasticsearch index settings definition.
 *
 * Elasticsearch index definition describes the settings of the index and
 * is used by IndexDefinition class.
 *
 * Example:
 *
 *   Index settings definition can be provided using the following code:
 *
 *     $settings = SettingsDefinition::create()
 *       ->addOptions([
 *         'number_of_shards' => 1,
 *         'number_of_replicas' => 0,
 *     ]);
 *
 * @see \Drupal\elasticsearch_helper\Elasticsearch\Index\IndexDefinition
 */
class SettingsDefinition extends DefinitionBase {

  use ObjectTrait;

}
