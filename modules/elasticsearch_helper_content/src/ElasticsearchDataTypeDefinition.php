<?php

namespace Drupal\elasticsearch_helper_content;

/**
 * Class ElasticsearchTypeDefinition
 */
class ElasticsearchDataTypeDefinition {

  /**
   * @var array
   */
  protected $definition;

  /**
   * ElasticsearchDataTypeDefinition constructor.
   *
   * @param \Drupal\elasticsearch_helper_content\ElasticsearchDataTypeProvider $elasticsearch_data_type_provider
   * @param array $values
   */
  public function __construct(ElasticsearchDataTypeProvider $elasticsearch_data_type_provider, array $values = []) {
    if (!empty($values['type']) && in_array($values['type'], $elasticsearch_data_type_provider->getDataTypes())) {
      $this->definition = $values;
    }
    else {
      throw new \InvalidArgumentException(t('Type "@type" is not a valid Elasticsearch data type.', [
        '@type' => $values['type'],
      ]));
    }
  }

  /**
   * Creates a new Elasticsearch type definition.
   *
   * @param string $type
   * @param array $options
   *
   * @return static
   *   A new DataDefinition object.
   */
  public static function create($type, array $options = []) {
    $definition['type'] = $type;
    $definition['options'] = $options;

    return new static(\Drupal::service('elasticsearch_helper_content.elasticsearch_data_type_provider'), $definition);
  }

  /**
   * Returns data type.
   *
   * @return string
   */
  public function getDataType() {
    return $this->definition['type'];
  }

  /**
   * Returns definition which can be ingested by Elasticsearch.
   *
   * @return array
   */
  public function getDefinition() {
    $type = $this->definition['type'];
    $options = $this->definition['options'];

    return array_merge(['type' => $type], $options);
  }

  /**
   * Adds options.
   *
   * @param array $options
   */
  public function addOptions(array $options) {
    $this->definition['options'] = array_merge($this->definition['options'], $options);
  }

}
