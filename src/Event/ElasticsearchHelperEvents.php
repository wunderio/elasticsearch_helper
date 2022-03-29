<?php

namespace Drupal\elasticsearch_helper\Event;

/**
 * Class ElasticsearchHelperEvents
 */
class ElasticsearchHelperEvents {

  /**
   * Name of the event fired when content is re-indexed.
   *
   * @Event
   *
   * @var string
   */
  const REINDEX = 'elasticsearch_helper.reindex';

}
