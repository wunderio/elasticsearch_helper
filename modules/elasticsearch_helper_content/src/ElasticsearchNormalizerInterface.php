<?php

namespace Drupal\elasticsearch_helper_content;

use Drupal\Component\Plugin\PluginInspectionInterface;

/**
 * Defines interface for Elasticsearch normalizer plugins.
 */
interface ElasticsearchNormalizerInterface extends PluginInspectionInterface {

  /**
   * Normalizes an object into a set of arrays/scalars.
   *
   * @param \Drupal\Core\Entity\ContentEntityInterface $object
   * @param array $context Context options for the normalizer
   *
   * @return array|string|int|float|bool
   */
  public function normalize($object, array $context = []);

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
   *      'string_value' => DataDefinition::create('string'),
   *      'number_value' => DataDefinition::create('integer'),
   *      'elements' => [
   *        'one' => DataDefinition::create('string'),
   *        'two' => DataDefinition::create('string'),
   *      ],
   *    ]
   *
   * @param array $context
   *
   * @return \Drupal\elasticsearch_helper_content\ElasticsearchDataTypeDefinition[]
   */
  public function getPropertyDefinitions(array $context = []);

}
