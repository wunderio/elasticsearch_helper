<?php

namespace Drupal\elasticsearch_helper_content;

use Drupal\Core\Field\FieldDefinitionInterface;

/**
 * Class ElasticsearchField
 */
class ElasticsearchField {

  /**
   * @var string
   */
  protected $name;

  /**
   * @var string
   */
  protected $label;

  /**
   * @var string
   */
  protected $type;

  /**
   * ElasticsearchField constructor.
   *
   * @param $name
   * @param $label
   * @param $type
   */
  public function __construct($name, $label, $type) {
    $this->name = $name;
    $this->label = $label;
    $this->type = $type;
  }

  /**
   * Creates Elasticsearch field object from field definition.
   *
   * @param \Drupal\Core\Field\FieldDefinitionInterface $field_definition
   *
   * @return \Drupal\elasticsearch_helper_content\ElasticsearchField
   */
  public static function createFromFieldDefinition(FieldDefinitionInterface $field_definition) {
    return new static(
      $field_definition->getName(),
      $field_definition->getLabel(),
      $field_definition->getType()
    );
  }

  /**
   * Returns field name.
   *
   * @return string
   */
  public function getName() {
    return $this->name;
  }

  /**
   * Returns field label.
   *
   * @return string
   */
  public function getLabel() {
    return $this->label;
  }

  /**
   * Returns field type.
   *
   * @return string
   */
  public function getType() {
    return $this->type;
  }

}
