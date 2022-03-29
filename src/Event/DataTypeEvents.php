<?php

namespace Drupal\elasticsearch_helper\Event;

/**
 * Class DataTypeEvents
 */
class DataTypeEvents {

  /**
   * Name of the event fired when data type definitions are built in data
   * type repository. Event subscribers can make changes to data type
   * definitions.
   *
   * @Event
   *
   * @var string
   */
  const BUILD = 'elasticsearch_helper.data_type_build';

}
