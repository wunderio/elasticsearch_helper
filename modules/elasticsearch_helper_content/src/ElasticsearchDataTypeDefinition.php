<?php

namespace Drupal\elasticsearch_helper_content;

use Drupal\Component\Utility\NestedArray;

/**
 * Class ElasticsearchTypeDefinition
 */
class ElasticsearchDataTypeDefinition {

  /**
   * @var string
   */
  protected $type;

  /**
   * @var array
   */
  protected $options = [];

  /**
   * @var \Drupal\elasticsearch_helper_content\ElasticsearchDataTypeDefinition[]
   */
  protected $properties = [];

  /**
   * @var \Drupal\elasticsearch_helper_content\ElasticsearchDataTypeDefinition[]
   */
  protected $fields = [];

  /**
   * @var array
   */
  protected $viewsOptions = [];

  /**
   * ElasticsearchDataTypeDefinition constructor.
   *
   * @param \Drupal\elasticsearch_helper_content\ElasticsearchDataTypeRepository $elasticsearch_data_type_repository
   * @param $type
   * @param array $options
   *
   * @throws \InvalidArgumentException
   * @throws \UnexpectedValueException
   */
  public function __construct(ElasticsearchDataTypeRepository $elasticsearch_data_type_repository, $type, array $options = []) {
    if ($elasticsearch_data_type_repository->dataTypeExists($type)) {
      $this->validateOptions($options);

      $this->type = $type;
      $this->options = $options;
      $this->viewsOptions = $elasticsearch_data_type_repository->getDataTypeViewsOptions($type);
    }
    else {
      throw new \InvalidArgumentException(t('Type "@type" is not a valid Elasticsearch data type.', [
        '@type' => $type,
      ]));
    }
  }

  /**
   * Creates a new Elasticsearch type definition.
   *
   * @param string $type
   * @param array $options
   *
   * @return self
   */
  public static function create($type, array $options = []) {
    return new static(\Drupal::service('elasticsearch_helper_content.elasticsearch_data_type_repository'), $type, $options);
  }

  /**
   * Returns data type.
   *
   * @return string
   */
  public function getDataType() {
    return $this->type;
  }

  /**
   * Returns definition which can be ingested by Elasticsearch.
   *
   * Definitions can contain sub-definitions (fields), therefore this
   * method can be re-used for sub-definitions as well.
   *
   * @param self|null $source
   *
   * @return array
   */
  public function getDefinition($source = NULL) {
    $source = $source ?: $this;

    $options = $source->options;

    if (!empty($source->properties)) {
      foreach ($source->properties as $field_name => $field_definition) {
        $options['properties'][$field_name] = $this->getDefinition($field_definition);
      }
    }
    elseif (!empty($source->fields)) {
      foreach ($source->fields as $field_name => $field_definition) {
        $options['fields'][$field_name] = $source->getDefinition($field_definition);
      }
    }

    return array_merge(['type' => $source->type], $options);
  }

  /**
   * Adds options.
   *
   * @param array $options
   *
   * @return $this
   */
  public function addOptions(array $options) {
    $this->validateOptions($options);
    $this->options = array_merge($this->options, $options);

    return $this;
  }

  /**
   * Returns options.
   *
   * @return array
   */
  public function getOptions() {
    return $this->options;
  }

  /**
   * Adds to views options.
   *
   * @param array $views_options
   *
   * @return $this
   */
  public function addViewsOptions(array $views_options) {
    $this->viewsOptions = NestedArray::mergeDeep($this->viewsOptions, $views_options);

    return $this;
  }

  /**
   * Returns views options.
   *
   * @return array
   */
  public function getViewsOptions() {
    return $this->viewsOptions;
  }

  /**
   * Adds a property.
   *
   * @param $field_name
   * @param \Drupal\elasticsearch_helper_content\ElasticsearchDataTypeDefinition $property
   *
   * @return $this
   */
  public function addProperty($field_name, ElasticsearchDataTypeDefinition $property) {
    $this->validatePropertyAddition();

    $this->properties[$field_name] = $property;

    return $this;
  }

  /**
   * Returns properties.
   *
   * @return \Drupal\elasticsearch_helper_content\ElasticsearchDataTypeDefinition[]
   */
  public function getProperties() {
    return $this->properties;
  }

  /**
   * Returns TRUE if definition has properties.
   *
   * @return bool
   */
  public function hasProperties() {
    return !empty($this->properties);
  }

  /**
   * Adds a field.
   *
   * @param $field_name
   * @param \Drupal\elasticsearch_helper_content\ElasticsearchDataTypeDefinition $field
   *
   * @return $this
   */
  public function addField($field_name, ElasticsearchDataTypeDefinition $field) {
    $this->validateFieldAddition();

    $this->fields[$field_name] = $field;

    return $this;
  }

  /**
   * Returns properties.
   *
   * @return \Drupal\elasticsearch_helper_content\ElasticsearchDataTypeDefinition[]
   */
  public function getFields() {
    return $this->fields;
  }

  /**
   * Returns TRUE if definition has fields.
   *
   * @return bool
   */
  public function hasFields() {
    return !empty($this->fields);
  }

  /**
   * Validates provided options.
   *
   * @param array $options
   *
   * @throws \UnexpectedValueException
   */
  protected function validateOptions(array $options = []) {
    if (isset($options['fields'])) {
      throw new \UnexpectedValueException(t('Fields should be added using @method method.', [
        '@method' => 'addFields()',
      ]));
    }
  }

  /**
   * Validates provided options.
   *
   * @throws \UnexpectedValueException
   */
  protected function validatePropertyAddition() {
    if (!empty($this->fields)) {
      throw new \UnexpectedValueException(t('Properties cannot be added if fields exist.'));
    }
  }

  /**
   * Validates field addition.
   *
   * @throws \UnexpectedValueException
   */
  protected function validateFieldAddition() {
    if (!empty($this->properties)) {
      throw new \UnexpectedValueException(t('Fields cannot be added if properties exist.'));
    }
  }

}
