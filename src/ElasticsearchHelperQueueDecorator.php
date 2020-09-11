<?php

namespace Drupal\elasticsearch_helper;

use Drupal\Core\Queue\QueueFactory;
use Drupal\Core\Site\Settings;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;

/**
 * Class ElasticsearchHelperQueueDecorator.
 *
 * Decorates the core QueueFactory to modify get() method
 * and return a custom Queue for elasticsearch helper.
 */
class ElasticsearchHelperQueueDecorator extends QueueFactory implements ContainerAwareInterface {

  use ContainerAwareTrait;

  /**
   * The inner queue service.
   *
   * @var \Drupal\Core\Queue\QueueFactory
   */
  protected $innerService;

  /**
   * Constructs a queue factory.
   */
  public function __construct(QueueFactory $inner, Settings $settings) {
    $this->innerService = $inner;
    parent::__construct($settings);
  }

  /**
   * {@inheritDoc}
   */
  public function get($name, $reliable = FALSE) {
    // Use Elasticsearch Helper's own Queue implementation.
    if ($name == 'elasticsearch_helper_indexing') {
      if (!isset($this->queues[$name])) {

        $this->queues[$name] = $this
          ->innerService
          ->container
          ->get('elasticsearch_helper.queue_factory')
          ->get($name);
      }
      return $this->queues[$name];
    }

    // Use core service for everything else.
    return $this->innerService->get($name, $reliable);
  }

}
