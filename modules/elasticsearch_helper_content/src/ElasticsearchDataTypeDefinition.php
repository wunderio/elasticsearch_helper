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

    throw new \InvalidArgumentException(t('Type "@type" is not a valid Elasticsearch data type.'));
  }

  /**
   * Creates a new Elasticsearch type definition.
   *
   * @param string $type
   *
   * @return static
   *   A new DataDefinition object.
   */
  public static function create($type) {
    $definition['type'] = $type;
    return new static(\Drupal::service('elasticsearch_helper_content.elasticsearch_data_type_provider'), $definition);
  }


  /**
   * {@inheritdoc}
   */
  public function getDataType() {
    return $this->definition['type'];
  }

}
