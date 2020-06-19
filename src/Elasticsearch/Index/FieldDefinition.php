<?php

namespace Drupal\elasticsearch_helper\Elasticsearch\Index;

use Drupal\elasticsearch_helper\Elasticsearch\DataType\DataType;
use Drupal\elasticsearch_helper\Elasticsearch\DefinitionBase;
use Drupal\elasticsearch_helper\Elasticsearch\ObjectTrait;

/**
 * Elasticsearch field definition.
 *
 * Elasticsearch mapping consists of fields (or properties) which vary by
 * type. Field definition contains the methods that allow to create index
 * mapping in an object oriented way.
 *
 * Field definition class has support for field parameters, field properties
 * (used with complex types like "object") and multi-fields.
 *
 * Example:
 *
 *   Company name is indexed as text with "keyword" multi-field saved as keyword
 *   for aggregation.
 *
 *     $company_name_field = FieldDefinition('text')
 *       ->addMultiField('keyword', FieldDefinition('keyword'));
 *
 *   Birth date is indexed as date in a specific format.
 *
 *     $birth_date_field = FieldDefinition('date')
 *       ->addOptions(['format' => 'yyyy-MM-dd']);
 *
 *   Person is indexed as an object with multiple sub-properties.
 *
 *     $person_field = FieldDefinition('object')
 *       ->addProperty('first_name', FieldDefinition('text'))
 *       ->addProperty('last_name', FieldDefinition('text'))
 *       ->addProperty('age', FieldDefinition('short'))
 *       ->addProperty('company_name', $company_name_field)
 *       ->addProperty('birth_date', $birth_date_field);
 *
 */
class FieldDefinition extends DefinitionBase {

  use ObjectTrait;

  /**
   * Data type of the field.
   *
   * @var \Drupal\elasticsearch_helper\Elasticsearch\DataType\DataType
   */
  protected $data_type;

  /**
   * Field properties (used with complex data types, e.g., object).
   *
   * @var \Drupal\elasticsearch_helper\Elasticsearch\Index\FieldDefinition[]
   */
  protected $properties = [];

  /**
   * Multi-fields on a field.
   *
   * @var \Drupal\elasticsearch_helper\Elasticsearch\Index\FieldDefinition[]
   */
  protected $multiFields = [];

  /**
   * FieldDefinition constructor.
   *
   * @param \Drupal\elasticsearch_helper\Elasticsearch\DataType\DataType $type
   * @param array $options
   *
   * @throws \InvalidArgumentException
   */
  public function __construct(DataType $type, array $options = []) {
    $this->data_type = $type;
    $this->addOptions($options);
  }

  /**
   * Creates new field definition.
   *
   * @param $type
   * @param array $options
   *
   * @return static
   *
   * @throws \InvalidArgumentException
   */
  public static function create($type, array $options = []) {
    return new static(DataType::create($type), $options);
  }

  /**
   * Returns data type instance.
   *
   * @return \Drupal\elasticsearch_helper\Elasticsearch\DataType\DataType
   */
  public function getDataType() {
    return $this->data_type;
  }

  /**
   * Adds a property.
   *
   * Properties are fields of an object.
   *
   * @param $field_name
   * @param \Drupal\elasticsearch_helper\Elasticsearch\Index\FieldDefinition $field
   *
   * @return self
   */
  public function addProperty($field_name, FieldDefinition $field) {
    $this->validatePropertyAddition();

    $this->properties[$field_name] = $field;

    return $this;
  }

  /**
   * Return a property.
   *
   * @param $field_name
   *
   * @return \Drupal\elasticsearch_helper\Elasticsearch\Index\FieldDefinition|null
   */
  public function getProperty($field_name) {
    return isset($this->properties[$field_name]) ? $this->properties[$field_name] : NULL;
  }

  /**
   * Returns object properties.
   *
   * @return \Drupal\elasticsearch_helper\Elasticsearch\Index\FieldDefinition[]
   */
  public function getProperties() {
    return $this->properties;
  }

  /**
   * Returns TRUE if field has properties.
   *
   * @return bool
   */
  public function hasProperties() {
    return !empty($this->properties);
  }

  /**
   * Adds a multi-field.
   *
   * @param $field_name
   * @param \Drupal\elasticsearch_helper\Elasticsearch\Index\FieldDefinition $field
   *
   * @return self
   */
  public function addMultiField($field_name, FieldDefinition $field) {
    $this->validateMultiFieldAddition();

    $this->multiFields[$field_name] = $field;

    return $this;
  }

  /**
   * Return a multi-field.
   *
   * @param $field_name
   *
   * @return \Drupal\elasticsearch_helper\Elasticsearch\Index\FieldDefinition|null
   */
  public function getMultiField($field_name) {
    return isset($this->multiFields[$field_name]) ? $this->multiFields[$field_name] : NULL;
  }

  /**
   * Returns properties.
   *
   * @return \Drupal\elasticsearch_helper\Elasticsearch\Index\FieldDefinition[]
   */
  public function getMultiFields() {
    return $this->multiFields;
  }

  /**
   * Returns TRUE if field has multi-fields.
   *
   * @return bool
   */
  public function hasMultiFields() {
    return !empty($this->multiFields);
  }

  /**
   * Returns field definition as an array.
   *
   * @param \Drupal\elasticsearch_helper\Elasticsearch\Index\FieldDefinition|NULL $source
   *
   * @return array
   */
  public function toArray(FieldDefinition $source = NULL) {
    $source = $source ?: $this;

    $result = $source->getOptions();

    // Add a type.
    $result['type'] = $source->getDataType()->getType();

    // Include properties.
    if ($source->hasProperties()) {
      foreach ($source->getProperties() as $field_name => $field_definition) {
        $result['properties'][$field_name] = $this->toArray($field_definition);
      }
    }
    // Include multi-fields into the array if available.
    elseif ($source->hasMultiFields()) {
      foreach ($source->getMultiFields() as $field_name => $field_definition) {
        $result['fields'][$field_name] = $this->toArray($field_definition);
      }
    }

    return $result;
  }

  /**
   * {@inheritdoc}
   *
   * @throws \InvalidArgumentException
   */
  protected function validateOptions(array $options = []) {
    if (isset($options['type'])) {
      throw new \InvalidArgumentException(sprintf('Data type should be added as an argument to the constructor of %s.', self::class));
    }
    elseif (isset($options['properties'])) {
      throw new \InvalidArgumentException('Properties should be added using addProperty() method.');
    }
    elseif (isset($options['fields'])) {
      throw new \InvalidArgumentException('Multi-fields should be added using addMultiField() method.');
    }
  }

  /**
   * Validates provided properties.
   *
   * @throws \InvalidArgumentException
   */
  protected function validatePropertyAddition() {
    if ($this->hasMultiFields()) {
      throw new \InvalidArgumentException('Properties cannot be added if multi-fields exist.');
    }
  }

  /**
   * Validates multi-field addition.
   *
   * @throws \InvalidArgumentException
   */
  protected function validateMultiFieldAddition() {
    if ($this->hasProperties()) {
      throw new \InvalidArgumentException('Multi-fields cannot be added if properties exist.');
    }
  }

}
