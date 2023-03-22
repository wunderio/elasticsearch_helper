<?php

namespace Drupal\elasticsearch_helper_test_subscriber\EventSubscriber;

use Drupal\elasticsearch_helper\Event\ElasticsearchEvents;
use Drupal\elasticsearch_helper\Event\ElasticsearchOperationRequestResultEvent;
use Drupal\elasticsearch_helper\Event\ElasticsearchOperations;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Drupal\Core\State\StateInterface;

/**
 * An event subscriber to test elasticsearch helper events.
 */
class TestElasticsearchHelperEvents implements EventSubscriberInterface {

  /**
   * The state service.
   *
   * @var \Drupal\Core\State\StateInterface
   */
  protected $state;

  /**
   * TestElasticsearchHelperEvents constructor.
   *
   * @param \Drupal\Core\State\StateInterface $state
   *   The state service.
   */
  public function __construct(StateInterface $state) {
    $this->state = $state;
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    $events = [];
    $events[ElasticsearchEvents::OPERATION_REQUEST_RESULT][] = ['onRequestResult'];
    return $events;
  }

  /**
   * Logs a message upon successful Elasticsearch operation.
   *
   * @param \Drupal\elasticsearch_helper\Event\ElasticsearchOperationRequestResultEvent $event
   */
  public function onRequestResult(ElasticsearchOperationRequestResultEvent $event) {
    // Get request result body.
    $result = $event->getResult()->getResultBody();

    // Get request wrapper.
    $request_wrapper = $event->getRequestWrapper();
    $object = $request_wrapper->getObject();

    // Get operation.
    $operation = $event->getRequestWrapper()->getOperation();

    // Customise the message for certain expected operations.
    if ($operation == ElasticsearchOperations::INDEX_CREATE) {
      $indexes = $this->state->get('created_indexes') ?? [];
      $indexes[] = $result['index'];
      $this->state->set('created_indexes', $indexes);
    }
    elseif ($operation == ElasticsearchOperations::DOCUMENT_INDEX) {
      $this->state->set('document_index', $object->id());
    }
    elseif ($operation == ElasticsearchOperations::DOCUMENT_GET) {
      $this->state->set('document_get', $object->getTitle());
    }
    elseif ($operation == ElasticsearchOperations::DOCUMENT_UPSERT) {
      $this->state->set('document_upsert', $object->getTitle());
    }
    elseif ($operation == ElasticsearchOperations::DOCUMENT_DELETE) {
      $this->state->set('document_delete', $object->id());
    }
    elseif ($operation == ElasticsearchOperations::QUERY_SEARCH) {
      if (isset($result['hits'], $result['hits']['total'], $result['hits']['total']['value'])) {
        $this->state->set('query_search', $result['hits']['total']['value']);
      }
    }
    elseif ($operation == ElasticsearchOperations::QUERY_MULTI_SEARCH) {
      if (isset($result['hits'], $result['hits']['total'], $result['hits']['total']['value'])) {
        $this->state->set('query_msearch', $result['hits']['total']['value']);
      }
    }

  }

}
