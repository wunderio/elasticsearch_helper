<?php

namespace Drupal\elasticsearch_helper\Event;

use Drupal\Component\EventDispatcher\Event;

/**
 * Class DataTypeDefinitionBuildEvent
 */
class DataTypeDefinitionBuildEvent extends Event {

  /**
   * @var array
   */
  protected $dataTypeDefinitions = [];

  /**
   * DataTypeDefinitionBuildEvent constructor.
   *
   * @param array $data_type_definitions
   */
  public function __construct(array $data_type_definitions) {
    $this->dataTypeDefinitions = $data_type_definitions;
  }

  /**
   * Returns data type definitions.
   *
   * @return array
   */
  public function &getDataTypeDefinitions() {
    return $this->dataTypeDefinitions;
  }

}
