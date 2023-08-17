<?php

namespace Drupal\elasticsearch_helper_test\EventSubscriber;

use Drupal\elasticsearch_helper\Elasticsearch\Index\FieldDefinition;
use Drupal\elasticsearch_helper\Event\ElasticsearchEvents;
use Drupal\elasticsearch_helper\Event\ElasticsearchOperationEvent;
use Drupal\elasticsearch_helper\Event\ElasticsearchOperations;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Mapping alteration event subscriber class.
 *
 * This example event subscriber alters the mapping of an index plugin.
 */
class AlterMappingEventSubscriber implements EventSubscriberInterface {

  /**
   * The targeted index name.
   *
   * @var string
   */
  protected $indexName = 'test_simple_node_index';

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    $events = [];
    $events[ElasticsearchEvents::OPERATION][] = ['onIndexCreate'];

    return $events;
  }

  /**
   * Alters the mapping of the index.
   *
   * @param \Drupal\elasticsearch_helper\Event\ElasticsearchOperationEvent $event
   */
  public function onIndexCreate(ElasticsearchOperationEvent $event) {
    if ($event->getOperation() == ElasticsearchOperations::INDEX_CREATE) {
      if ($event->getObject() == $this->indexName) {
        // Get the context variable by reference.
        $context = &$event->getContext();

        /** @var \Drupal\elasticsearch_helper\Elasticsearch\Index\IndexDefinition $index_definition */
        $index_definition = &$context['index_definition'];
        $mapping_definition = $index_definition->getMappingDefinition();

        // Add the "extra" property.
        $mapping_definition->addProperty('extra', FieldDefinition::create('keyword'));
      }
    }
  }

}
