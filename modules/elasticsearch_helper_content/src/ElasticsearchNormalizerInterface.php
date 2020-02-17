<?php

namespace Drupal\elasticsearch_helper_content;

use Drupal\Component\Plugin\ConfigurablePluginInterface;
use Drupal\Component\Plugin\PluginInspectionInterface;
use Drupal\Core\Plugin\PluginFormInterface;

/**
 * Defines interface for Elasticsearch normalizer plugins.
 */
interface ElasticsearchNormalizerInterface extends PluginInspectionInterface, ConfigurablePluginInterface, PluginFormInterface {

  /**
   * Returns property definitions.
   *
   * This method should return the same array structure that normalize()
   * method returns. Each property in the structure should define the
   * data definition of the property.
   *
   * If normalizer returns a single non-keyed property, this method should
   * return a single data definition object.
   *
   * Example:
   *
   *   - if normalize() returns:
   *    [
   *      'string_value' => "foo",
   *      'number_value' => 123,
   *      'elements' => [
   *        'one' => 'alpha',
   *        'two' => 'beta',
   *      ],
   *    ]
   *   - getPropertyDefinitions() should return the following:
   *    [
   *      'string_value' => ElasticsearchDataTypeDefinition::create('string'),
   *      'number_value' => ElasticsearchDataTypeDefinition::create('integer'),
   *      'elements' => ElasticsearchDataTypeDefinition::create('object')
   *        ->addProperty('one' => ElasticsearchDataTypeDefinition::create('string')),
   *        ->addProperty('two' => ElasticsearchDataTypeDefinition::create('string')),
   *    ]
   *
   * @return \Drupal\elasticsearch_helper_content\ElasticsearchDataTypeDefinition[]
   */
  public function getPropertyDefinitions();

}
