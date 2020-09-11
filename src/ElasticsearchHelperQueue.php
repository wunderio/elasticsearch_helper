<?php

namespace Drupal\elasticsearch_helper;

use Drupal\Core\Queue\DatabaseQueue;

/**
 * Class ElasticsearchHelperQueue.
 */
class ElasticsearchHelperQueue extends DatabaseQueue {

  /**
   * The database table name for the ES helper queue.
   */
  const TABLE_NAME = 'queue_elasticsearch_helper';

  /**
   * {@inheritDoc}
   */
  protected function doCreateItem($data) {
    $serialized = serialize($data);

    $query = $this->connection
      ->merge(static::TABLE_NAME)
      ->keys(['name' => $this->name, 'data' => $serialized])
      ->fields([
        'name' => $this->name,
        'data' => $serialized,
        'created' => time(),
        'entity_type' => $data['entity_type'] ?: '',
      ]);

    return $query->execute();
  }

  /**
   * {@inheritdoc}
   */
  public function schemaDefinition() {

    $schema = parent::schemaDefinition();

    $schema['fields']['entity_type'] = [
      'type' => 'varchar_ascii',
      'length' => 255,
      'not null' => TRUE,
      'default' => '',
      'description' => 'The entity type id.',
    ];

    $schema['indexes']['entity_type'] = ['entity_type'];

    return $schema;
  }

}
