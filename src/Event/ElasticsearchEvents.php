<?php

namespace Drupal\elasticsearch_helper\Event;

/**
 * Class ElasticsearchEvents
 */
class ElasticsearchEvents {

  /**
   * Generic Elasticsearch operation event.
   *
   * Name of the event fired when Elasticsearch operation method is called
   * (e.g., index, update, delete).
   *
   * @Event
   *
   * @var string
   */
  const OPERATION = 'elasticsearch_helper.operation';

  /**
   * Elasticsearch operation request event.
   *
   * Name of the event fired when Elasticsearch operation is about to be
   * handed to Elasticsearch client.
   *
   * @Event
   *
   * @var string
   */
  const OPERATION_REQUEST = 'elasticsearch_helper.operation_request';

  /**
   * Elasticsearch operation result event.
   *
   * Name of the event fired when Elasticsearch operation response result is
   * received.
   *
   * @Event
   *
   * @var string
   */
  const OPERATION_RESULT = 'elasticsearch_helper.operation_result';

  /**
   * Elasticsearch operation error event.
   *
   * Name of the event fired when throwable is thrown during Elasticsearch
   * operation.
   *
   * @Event
   *
   * @var string
   */
  const OPERATION_ERROR = 'elasticsearch_helper.operation_error';

}
