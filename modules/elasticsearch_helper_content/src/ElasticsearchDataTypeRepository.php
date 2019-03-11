<?php

namespace Drupal\elasticsearch_helper_content;

use Drupal\Component\Utility\NestedArray;

/**
 * Class ElasticsearchDataTypeRepository
 */
class ElasticsearchDataTypeRepository {

  /**
   * @var array
   */
  protected $defaultOptions = [
    'views' => [
      'handlers' => [
        'field' => [
          'id' => 'elasticsearch_content_string'
        ],
        'filter' => [
          'id' => 'string',
        ],
      ],
    ],
  ];

  /**
   * @var array
   */
  protected $dataTypes = [
    // Text data types.
    'text' => [],
    'keyword' => [],
    // Numeric data types.
    'long' => [],
    'integer' => [
      'views' => [
        'handlers' => [
          'field' => [
            'id' => 'elasticsearch_content_numeric',
          ],
        ],
      ],
    ],
    'short' => [],
    'byte' => [],
    'double' => [
      'views' => [
        'handlers' => [
          'field' => [
            'id' => 'elasticsearch_content_numeric',
            'float' => TRUE,
          ],
        ],
      ],
    ],
    'float' => [
      'views' => [
        'handlers' => [
          'field' => [
            'id' => 'elasticsearch_content_numeric',
            'float' => TRUE,
          ],
        ],
      ],
    ],
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
    // Complex data types.
    'object' => [],
    'nested' => [],
  ];

  /**
   * Returns a list of Elasticsearch data type machine names.
   *
   * @return array
   */
  public function getDataTypes() {
    return array_keys($this->dataTypes);
  }

  /**
   * Returns TRUE if data type exists.
   *
   * @param $type
   *
   * @return bool
   */
  public function dataTypeExists($type) {
    return isset($this->dataTypes[$type]);
  }

  /**
   * Returns data type options.
   *
   * @param $type
   *
   * @return array
   */
  public function getDataTypeOptions($type) {
    $data_type_options = !empty($this->dataTypes[$type]) ? $this->dataTypes[$type] : [];

    return NestedArray::mergeDeep($this->defaultOptions, $data_type_options);
  }

  /**
   * Returns data type views options.
   *
   * @param $type
   *
   * @return array
   */
  public function getDataTypeViewsOptions($type) {
    $data_type_options = $this->getDataTypeOptions($type);

    return isset($data_type_options['views']) ? $data_type_options['views'] : [];
  }

}
