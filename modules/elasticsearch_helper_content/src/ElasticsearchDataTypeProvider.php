<?php

namespace Drupal\elasticsearch_helper_content;

/**
 * Class ElasticsearchDataTypeProvider
 */
class ElasticsearchDataTypeProvider {

  /**
   * @var array
   */
  protected $dataTypes = [
    // Text data types.
    'text' => [],
    'keyword' => [],
    // Numeric data types.
    'long' => [],
    'integer' => [],
    'short' => [],
    'byte' => [],
    'double' => [],
    'float' => [],
    'half_float' => [],
    'scaled_float' => [],
    // Date data types.
    'date' => [],
    // Boolean data types.
    'boolean' => [],
    // Boolean data types.
    'binary' => [],
    // Range data types.
    'integer_range' => [],
    'float_range' => [],
    'long_range' => [],
    'double_range' => [],
    'date_range' => [],
    // Geo-point data types.
    'geo_point' => [],
    // Geo-shape data types.
    'geo_shape' => [],
    // IP data types.
    'ip' => [],
  ];

  /**
   * Returns a list of Elasticsearch data type machine names.
   *
   * @return array
   */
  public function getDataTypes() {
    return array_keys($this->dataTypes);
  }

}
