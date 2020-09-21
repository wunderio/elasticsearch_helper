<?php


namespace Drupal\elasticsearch_helper\Event;

/**
 * Operation permission trait.
 */
trait OperationPermissionTrait {

  /**
   * {@inheritdoc}
   */
  protected $allowed = TRUE;

  /**
   * {@inheritdoc}
   */
  public function isOperationAllowed() {
    return $this->allowed;
  }

  /**
   * {@inheritdoc}
   */
  public function allowOperation() {
    $this->allowed = TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function forbidOperation() {
    $this->allowed = FALSE;
  }

}
