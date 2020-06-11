<?php

namespace Drupal\elasticsearch_helper\Event;

/**
 * Class ElasticsearchEvents
 */
class ElasticsearchEvents {

  /**
   * Name of the event fired when Elasticsearch operation method is called
   * (e.g., index, update, delete).
   *
   * @Event
   *
   * @var string
   */
  const OPERATION = 'elasticsearch_helper.operation';

  /**
   * Name of the event fired when Elasticsearch operation is about to be
   * handed to Elasticsearch client.
   *
   * @Event
   *
   * @var string
   */
  const OPERATION_REQUEST = 'elasticsearch_helper.operation_request';

}
