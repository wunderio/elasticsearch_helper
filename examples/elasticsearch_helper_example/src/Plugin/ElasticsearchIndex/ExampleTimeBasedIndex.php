<?php

namespace Drupal\elasticsearch_helper_example\Plugin\ElasticsearchIndex;

use Drupal\elasticsearch_helper\Elasticsearch\Index\FieldDefinition;
use Drupal\elasticsearch_helper\Elasticsearch\Index\MappingDefinition;
use Drupal\elasticsearch_helper\Event\ElasticsearchOperations;

/**
 * @ElasticsearchIndex(
 *   id = "example_time_based_index",
 *   label = @Translation("Example time-based index"),
 *   indexName = "example-time-based-{year}{month}",
 *   entityType = "node"
 * )
 */
class ExampleTimeBasedIndex extends IndexBase {

  /**
   * {@inheritdoc}
   *
   * @param \Drupal\node\Entity\Node $source
   */
  public function serialize($source, $context = []) {
    $data['id'] = $source->id();
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
      $template_name = $this->getPluginId();

      if (!$this->client->indices()->existsTemplate(['name' => $template_name])->asBool()) {
        $callback = [$this->client->indices(), 'putTemplate'];

        $request_params = [
          'name' => $template_name,
          'body' => [
            'index_patterns' => $this->indexNamePattern(),
          ] + $this->getIndexDefinition()->toArray(),
        ];

        // Create the template.
        $request_wrapper = $this->createRequest($operation, $callback, $request_params);
        $request_wrapper->execute();
      }
    }
    catch (\Throwable $e) {
      $request_wrapper = isset($request_wrapper) ? $request_wrapper : NULL;
      $this->dispatchOperationErrorEvent($e, $operation, $request_wrapper);
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
      ->addProperty('id', FieldDefinition::create('keyword'))
      ->addProperty('created', $created_field)
      ->addProperty('year', $disabled_field)
      ->addProperty('month', $disabled_field);
  }

}
