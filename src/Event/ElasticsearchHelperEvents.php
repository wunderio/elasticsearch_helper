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

  /**
   * Name of the event fired before content is serialized.
   *
   * @Event
   *
   * @var string
   */
  const PRE_SERIALIZE = 'elasticsearch_helper.pre_serialize';

  /**
   * Name of the event fired after content is serialized.
   *
   * @Event
   *
   * @var string
   */
  const POST_SERIALIZE = 'elasticsearch_helper.post_serialize';

}
