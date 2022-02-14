<?php

namespace Drupal\elasticsearch_helper_example\Plugin\ElasticsearchIndex;

use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\elasticsearch_helper\Plugin\ElasticsearchIndexBase;
use Drupal\elasticsearch_helper\Elasticsearch\Index\FieldDefinition;
use Drupal\elasticsearch_helper\Elasticsearch\Index\MappingDefinition;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Define a custom index that uses a custom client.
 *
 * @ElasticsearchIndex(
 *   id = "custom_client_index",
 *   label = @Translation("Index with custom client"),
 *   indexName = "custom_client_index",
 *   typeName = "node",
 *   entityType = "node"
 * )
 */
class IndexWithCustomClient extends ElasticsearchIndexBase implements ContainerFactoryPluginInterface {

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      // Inject custom client.
      $container->get('elasticsearch_helper.client.custom'),
      $container->get('serializer'),
      $container->get('logger.factory')->get('elasticsearch_helper')
    );
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
