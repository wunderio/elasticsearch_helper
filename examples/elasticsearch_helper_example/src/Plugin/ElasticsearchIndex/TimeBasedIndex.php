<?php

namespace Drupal\elasticsearch_helper_example\Plugin\ElasticsearchIndex;

use Drupal\elasticsearch_helper\Elasticsearch\Index\FieldDefinition;
use Drupal\elasticsearch_helper\Elasticsearch\Index\MappingDefinition;
use Drupal\elasticsearch_helper\Event\ElasticsearchOperations;
use Drupal\elasticsearch_helper\Plugin\ElasticsearchIndexBase;

/**
 * @ElasticsearchIndex(
 *   id = "time_based_index",
 *   label = @Translation("Example time-based index"),
 *   indexName = "time-based-{year}{month}",
 *   typeName = "node",
 *   entityType = "node"
 * )
 */
class TimeBasedIndex extends ElasticsearchIndexBase {

  /**
   * {@inheritdoc}
   */
  public function serialize($source, $context = []) {
    /** @var \Drupal\node\Entity\Node $source */

    $data = parent::serialize($source);

    // Add the creation date.
    $data['created'] = $source->getCreatedTime();

    // Add attributes matching the placeholders in the indexName.
    $data['year'] = date('Y', $source->getCreatedTime());
    $data['month'] = date('m', $source->getCreatedTime());

    return $data;
  }

  /**
   * {@inheritdoc}
   */
  public function setup() {
    try {
      $operation = ElasticsearchOperations::INDEX_TEMPLATE_CREATE;

      $request_params = [
        'name' => $this->getPluginId(),
        'body' => [
          // Any index matching the pattern will get the given index configuration.
          'template' => $this->indexNamePattern(),
          'mappings' => $this->getMappingDefinition()->toArray(),
        ],
      ];

      // Create the index.
      $callback = [$this->client->indices(), 'putTemplate'];
      $request_event = $this->dispatchOperationRequestEvent($operation, $callback, $request_params);

      $result = call_user_func_array($request_event->getCallback(), $request_event->getCallbackParameters());
      $this->dispatchOperationResultEvent($result, $operation, NULL, $request_params);
    } catch (\Exception $e) {
      $request_params = isset($request_params) ? $request_params : NULL;
      $this->dispatchOperationExceptionEvent($e, $operation, NULL, $request_params);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getMappingDefinition(array $context = []) {
    // Define created field.
    $created_field = FieldDefinition::create('date')
      ->addOption('format', 'epoch_second');

    // Define a field that is not stored.
    $disabled_field = FieldDefinition::create('object')
      ->addOption('enabled', FALSE);

    return MappingDefinition::create()
      ->addProperty('created', $created_field)
      ->addProperty('year', $disabled_field)
      ->addProperty('month', $disabled_field);
  }

}
