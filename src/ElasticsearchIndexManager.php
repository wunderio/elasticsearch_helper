<?php

/**
 * Provides the Elasticsearch index plugin manager.
 */
class ElasticsearchIndexManager {

    /**
   * Index an entity into any matching indices.
   */
  public function getDefinitions() {
    ctools_include('plugins');
    $plugins = ctools_get_plugins('elasticsearch_helper', 'elasticsearch_helper_index');

    return $plugins;
  }

  /**
   * Index an entity into any matching indices.
   */
  public function indexEntity($entity, $type) {
    foreach ($this->getDefinitions() as $plugin) {
      if (isset($plugin['entityType']) && $type == $plugin['entityType']) {
        $entity_info = entity_get_info($plugin['entityType']);
        if (!empty($plugin['bundle']) && $plugin['bundle'] != $entity->{$entity_info['entity keys']['bundle']}) {
          // Do not index if defined plugin bundle differs from entity bundle.
          continue;
        }

        try {
          if ($function = ctools_plugin_get_function($plugin, 'index_callback')) {
            $function($entity, $plugin);
          }

        }
        catch (Exception $e) {
        watchdog('Elasticsearch indexing failed: @message', [
            '@message' => $e->getMessage(),
          ], WATCHDOG_ERROR);
        }
      }
    }
  }

  /**
   * Delete an entity from any matching indices.
   */
  public function deleteEntity($entity, $type) {
    foreach ($this->getDefinitions() as $plugin) {
      if (isset($plugin['entityType']) && $type == $plugin['entityType']) {
        if (!empty($plugin['bundle']) && $plugin['bundle'] != $entity->bundle()) {
          // Do not index if defined plugin bundle differs from entity bundle.
          continue;
        }

        try {
          if ($function = ctools_plugin_get_function($plugin, 'delete_callback')) {
            $function($entity, $plugin);
          }

        }
        catch (Exception $e) {
        watchdog('Elasticsearch deleting failed: @message', [
            '@message' => $e->getMessage(),
          ], WATCHDOG_ERROR);
        }
      }
    }
  }

  /**
   * Reindex elasticsearch with all entities.
   */
  public function reindex($indices = []) {

    foreach ($this->getDefinitions() as $plugin) {
      if (empty($indices) || in_array($plugin['id'], $indices)) {

        if ($plugin['entityType']) {
          $query = $this->entityTypeManager->getStorage($plugin['entityType'])->getQuery();

          $entity_type = $this->entityTypeManager->getDefinition($plugin['entityType']);

          if ($plugin['bundle']) {
            $query->condition($entity_type->getKey('bundle'), $plugin['bundle']);
          }

          $result = $query->execute();

          foreach ($result as $entity_id) {
            $this->queue->createItem([
              'entity_type' => $entity_type->id(),
              'entity_id' => $entity_id,
            ]);
          }
          $this->logger->notice("Marked indices to be reindex on next cronrun");
        }
      }
    }
  }

}
