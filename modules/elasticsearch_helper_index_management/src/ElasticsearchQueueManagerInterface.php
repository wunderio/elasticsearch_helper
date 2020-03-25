<?php

namespace Drupal\elasticsearch_helper_index_management;

/**
 * Interface ElasticsearchQueueManagerInterface.
 */
interface ElasticsearchQueueManagerInterface {

  /**
   * Get the current queue status of the index.
   *
   * @param string $index_id
   *   The plugin index id.
   */
  public function getStatus($index_id);

  /**
   * Get total items count.
   *
   * @param string $index_id
   *   The plugin index id.
   */
  public function getTotal($index_id);

  /**
   * Get total processed items count.
   *
   * @param string $index_id
   *   The plugin index id.
   */
  public function getProcessed($index_id);

  /**
   * Get total number of items that has errors.
   */
  public function getTotalErrors($index_id);

  /**
   * Add all items from index to queue.
   *
   * @param string $index_id
   *   The plugin index id.
   */
  public function addAll($index_id);

  /**
   * Clear all items from queue.
   *
   * @param string $index_id
   *   The plugin index id.
   */
  public function clear($index_id);

  /**
   * Get items from queue.
   *
   * @param string $index_id
   *   The plugin index id.
   *
   * @return array
   *   Array of queue items.
   */
  public function getItems($index_id);

  /**
   * Get one item from queue.
   *
   * @param int $id
   *   The id of the queue item.
   *
   * @return object
   *   The queue item.
   */
  public function getItem($id);

  /**
   * Set the status of the queue item.
   *
   * @param int $id
   *   The id of the queue item.
   * @param int $status
   *   Status value. 1 or 0.
   */
  public function setStatus($id, $status);

  /**
   * Set the status of the queue item.
   *
   * @param int $id
   *   The id of the queue item.
   * @param int $error
   *   Error status value. 1 or 0.
   */
  public function setError($id, $error);

}
