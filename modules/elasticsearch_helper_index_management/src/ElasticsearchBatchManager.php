<?php

namespace Drupal\elasticsearch_helper_index_management;

use Elasticsearch\Common\Exceptions\ElasticsearchException;

/**
 * Batch manager class.
 */
class ElasticsearchBatchManager {

  /**
   * Process one item from re-index queue.
   *
   * @param int $id
   *   The queue item's id.
   * @param array $context
   *   The batch context.
   */
  public static function processOne($id, &$context) {
    /** @var \Drupal\elasticsearch_helper_index_management\ElasticsearchQueueManager $queue_manager */
    $queue_manager = \drupal::service('elasticsearch_helper_index_management.queue_manager');

    /** @var \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager */
    $entity_type_manager = \Drupal::service('entity_type.manager');

    if ($item = $queue_manager->getItem($id)) {
      if ($entity = $entity_type_manager->getStorage($item->entity_type)->load($item->entity_id)) {
        if (self::indexEntity($entity)) {
          $queue_manager->setStatus($id, 1);
          $queue_manager->setError($id, 0);
          $context['results'][] = $id;
        }
        else {
          $queue_manager->setError($id, 1);
        }
      }
    }
  }

  /**
   * Callback when batch process is done.
   */
  public static function processFinished($success, $results, $operations) {
    if ($success) {
      \Drupal::messenger()->addMessage(t('@count items processed', ['@count' => count($results)]));
    }
    else {
      \Drupal::messenger()->addMessage(t('An error occured'), 'error');
    }
  }

  /**
   * Index a single entity.
   */
  protected static function indexEntity($entity) {
    /** @var \Drupal\elasticsearch_helper\Plugin\ElasticsearchIndexManager $index_manager */
    $index_manager = \Drupal::service('plugin.manager.elasticsearch_index.processor');

    foreach ($index_manager->getDefinitions() as $plugin) {
      if (isset($plugin['entityType']) && $entity->getEntityTypeId() == $plugin['entityType']) {
        if (!empty($plugin['bundle']) && $plugin['bundle'] != $entity->bundle()) {
          // Do not index if defined plugin bundle differs from entity bundle.
          continue;
        }

        try {
          $index_manager->createInstance($plugin['id'])->index($entity);
          return TRUE;
        }
        catch (ElasticsearchException $e) {
          return FALSE;
        }
      }
    }
  }

}
