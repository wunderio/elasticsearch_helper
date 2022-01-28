<?php

namespace Drupal\elasticsearch_helper\Event;

use Drupal\Core\Entity\EntityInterface;
use Symfony\Component\EventDispatcher\Event;

/**
 * Elasticsearch Helper entity operation event.
 *
 * This event should be used when an entity is being inserted or updated
 * via a hook_entity_insert() or hook_entity_update() hook.
 */
class ElasticsearchHelperEntityOperationEvent extends Event {

  /**
   * An entity on which the operation is performed.
   *
   * @var \Drupal\Core\Entity\EntityInterface
   */
  protected $entity;

  /**
   * Context of the operation if available.
   *
   * @var array
   */
  protected $context = [];

  /**
   * Operation being performed.
   *
   * Typically, an operation is insert or update.
   *
   * @var
   */
  protected $operation;

  /**
   * ElasticsearchHelperEntityOperationEvent constructor.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   * @param string $operation
   * @param array $context
   */
  public function __construct(EntityInterface $entity, $operation, array $context = []) {
    $this->entity = $entity;
    $this->operation = $operation;
    $this->context = $context;
  }

  /**
   * Returns the entity.
   *
   * @return \Drupal\Core\Entity\EntityInterface
   */
  public function getEntity() {
    return $this->entity;
  }

  /**
   * Returns entity operation.
   *
   * @return string
   */
  public function getOperation() {
    return $this->operation;
  }

  /**
   * Returns the context of the operation if available.
   *
   * @return array
   */
  public function getContext() {
    return $this->context;
  }

}
