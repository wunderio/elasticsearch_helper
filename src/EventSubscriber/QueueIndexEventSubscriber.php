<?php

namespace Drupal\elasticsearch_helper\EventSubscriber;

use Drupal\Core\Queue\SuspendQueueException;
use Drupal\elasticsearch_helper\Plugin\QueueWorker\IndexingQueueWorker;
use Drupal\elasticsearch_helper\Event\ElasticsearchEvents;
use Drupal\elasticsearch_helper\Event\ElasticsearchOperationErrorEvent;
use Elastic\Transport\Exception\NoNodeAvailableException;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Queue indexing event subscriber.
 */
class QueueIndexEventSubscriber implements EventSubscriberInterface {

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    $events = [];
    $events[ElasticsearchEvents::OPERATION_ERROR][] = ['onQueueIndexError'];

    return $events;
  }

  /**
   * Suspends queue execution if no nodes are available.
   *
   * @param \Drupal\elasticsearch_helper\Event\ElasticsearchOperationErrorEvent $event
   */
  public function onQueueIndexError(ElasticsearchOperationErrorEvent $event) {
    $index_with_queue = &drupal_static(IndexingQueueWorker::QUEUE_INDEXING_VAR_NAME);

    if ($index_with_queue && $event->getError() instanceof NoNodeAvailableException) {
      throw new SuspendQueueException();
    }
  }

}
