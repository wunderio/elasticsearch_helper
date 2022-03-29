<?php

namespace Drupal\elasticsearch_helper;

use Drupal\Core\Queue\QueueDatabaseFactory;

/**
 * Factory class for Elasticsearch Helper Queue.
 */
class ElasticsearchHelperQueueFactory extends QueueDatabaseFactory {

  /**
   * {@inheritdoc}
   */
  public function get($name) {
    return new ElasticsearchHelperQueue($name, $this->connection);
  }

}
