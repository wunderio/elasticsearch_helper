<?php

namespace Drupal\elasticsearch_helper\Elasticsearch\DataType;

/**
 * Interface DataTypeRepositoryInterface
 */
interface DataTypeRepositoryInterface {

  /**
   * Returns all data type definitions.
   *
   * @return array
   */
  public function getTypeDefinitions();

  /**
   * Returns data type definition.
   *
   * @param $type
   *
   * @return array
   */
  public function getTypeDefinition($type);

}
