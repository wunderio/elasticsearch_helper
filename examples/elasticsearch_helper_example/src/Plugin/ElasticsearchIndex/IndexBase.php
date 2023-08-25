<?php

namespace Drupal\elasticsearch_helper_example\Plugin\ElasticsearchIndex;

use Drupal\elasticsearch_helper\Elasticsearch\Index\FieldDefinition;
use Drupal\elasticsearch_helper\Elasticsearch\Index\MappingDefinition;
use Drupal\elasticsearch_helper\Plugin\ElasticsearchIndexBase;

/**
 * Index base class.
 */
abstract class IndexBase extends ElasticsearchIndexBase {

  /**
   * Defines the normalizer class name.
   *
   * @var string
   */
  protected $normalizerClass = '\Drupal\elasticsearch_helper_example\Plugin\Normalizer\ExampleNodeNormalizer';

  /**
   * Returns node normalizer.
   *
   * @return \Drupal\elasticsearch_helper_example\Plugin\Normalizer\ExampleNodeNormalizer
   */
  protected function getNormalizer() {
    return new $this->normalizerClass(
      \Drupal::service('entity_type.manager'),
      \Drupal::service('entity_type.repository'),
      \Drupal::service('entity_field.manager')
    );
  }

  /**
   * {@inheritdoc}
   *
   * @param \Drupal\node\Entity\Node $source
   *
   * @see self::getNormalizer()
   */
  public function serialize($source, $context = []) {
    // Typically, normalizers are handled by serializer service where
    // conforming normalizer is picked by the means of weight. In this case
    // a specific normalizer class is used to normalize the object.
    $data = $this->getNormalizer()->normalize($source, 'elasticsearch_helper', $context);

    return $data;
  }

  /**
   * {@inheritdoc}
   */
  public function getMappingDefinition(array $context = []) {
    $user_property = FieldDefinition::create('object')
      ->addProperty('uid', FieldDefinition::create('integer'))
      ->addProperty('name', FieldDefinition::create('keyword'));

    return MappingDefinition::create()
      ->addProperty('id', FieldDefinition::create('integer'))
      ->addProperty('uuid', FieldDefinition::create('keyword'))
      ->addProperty('title', FieldDefinition::create('text'))
      ->addProperty('status', FieldDefinition::create('keyword'))
      ->addProperty('user', $user_property);
  }

}
