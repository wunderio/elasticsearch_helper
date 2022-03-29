<?php

namespace Drupal\elasticsearch_helper;

use Drupal\Core\Queue\QueueFactory;
use Drupal\Core\Site\Settings;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;

/**
 * QueueFactory decorator.
 *
 * Decorates the core QueueFactory to modify get() method
 * and return a custom Queue for Elasticsearch Helper.
 */
class ElasticsearchHelperQueueFactoryDecorator extends QueueFactory implements ContainerAwareInterface {

  /**
   * The inner queue service.
   *
   * @var \Drupal\Core\Queue\QueueFactory
   */
  protected $innerService;

  /**
   * ElasticsearchHelperQueueFactoryDecorator constructor.
   *
   * @param \Drupal\Core\Queue\QueueFactory $inner
   * @param \Drupal\Core\Site\Settings $settings
   */
  public function __construct(QueueFactory $inner, Settings $settings) {
    parent::__construct($settings);

    $this->innerService = $inner;
  }

  /**
   * {@inheritdoc}
   */
  public function get($name, $reliable = FALSE) {
    // Use module's own queue implementation.
    if ($name == 'elasticsearch_helper_indexing') {
      if (!isset($this->queues[$name])) {
        $queue = $this->innerService->container->get('elasticsearch_helper.queue_factory')->get($name);
        $this->queues[$name] = $queue;
      }

      return $this->queues[$name];
    }

    // Use core service for everything else.
    return $this->innerService->get($name, $reliable);
  }

}
