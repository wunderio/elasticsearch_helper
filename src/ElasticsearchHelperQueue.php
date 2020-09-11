<?php

namespace Drupal\elasticsearch_helper;

use Drupal\Core\Queue\DatabaseQueue;

/**
 * Class ElasticsearchHelperQueue.
 */
class ElasticsearchHelperQueue extends DatabaseQueue {

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
      ]);

    return $query->execute();
  }

}
