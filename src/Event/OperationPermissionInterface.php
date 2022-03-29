<?php

namespace Drupal\elasticsearch_helper\Event;

/**
 * Defines operation permission interface.
 *
 * Operation permission is used to indicate if operation can be performed.
 *
 * If should be used on events where callback is invoked.
 */
interface OperationPermissionInterface {

  /**
   * Return TRUE if operation is allowed to be performed.
   *
   * @return bool
   */
  public function isOperationAllowed();

  /**
   * Allow the operation to be performed.
   */
  public function allowOperation();

  /**
   * Disallow the operation to be performed.
   */
  public function forbidOperation();

}
