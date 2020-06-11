<?php

namespace Drupal\elasticsearch_helper\Elasticsearch\DataType;

/**
 * Class DataType
 */
class DataType {

  /**
   * @var string
   */
  protected $type;

  /**
   * @var array
   */
  protected $definition = [];

  /**
   * DataType constructor.
   *
   * @param $type
   * @param array $definition
   */
  public function __construct($type, array $definition) {
    $this->type = $type;
    $this->definition = $definition;
  }

  /**
   * Returns an instance of a data type.
   *
   * @param $type
   *
   * @return static
   */
  public static function create($type) {
    static $types = [];

    if (empty($types[$type])) {
      $data_type_repository = self::getDataTypeRepository();

      $types[$type] = new static(
        $type,
        $data_type_repository->getTypeDefinition($type)
      );
    }

    return $types[$type];
  }

  /**
   * Returns data type repository instance.
   *
   * @return \Drupal\elasticsearch_helper\Elasticsearch\DataType\DataTypeRepositoryInterface
   */
  public static function getDataTypeRepository() {
    return \Drupal::service('elasticsearch_helper.data_type_repository');
  }

  /**
   * Returns data type.
   *
   * @return string
   */
  public function getType() {
    return $this->type;
  }

  /**
   * Returns data type definition.
   */
  public function getDefinition() {
    $this->definition;
  }

}
