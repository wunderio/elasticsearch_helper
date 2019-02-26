<?php

namespace Drupal\elasticsearch_helper_content;

/**
 * Class ElasticsearchEntityNormalizerBase
 */
abstract class ElasticsearchEntityNormalizerBase extends ElasticsearchNormalizerBase {

  /**
   * Returns core property definitions that are shared between entity and
   * entity field normalizers.
   *
   * @param array $context
   *
   * @return \Drupal\elasticsearch_helper_content\ElasticsearchDataTypeDefinition[]|\Drupal\elasticsearch_helper_content\ElasticsearchDataTypeDefinition
   */
  public function getCorePropertyDefinitions(array $context = []) {
    return [
      'id' => ElasticsearchDataTypeDefinition::create('integer'),
      'uuid' => ElasticsearchDataTypeDefinition::create('keyword'),
      'entity_type' => ElasticsearchDataTypeDefinition::create('keyword'),
      'bundle' => ElasticsearchDataTypeDefinition::create('keyword'),
      'entity_type_label' => ElasticsearchDataTypeDefinition::create('keyword'),
      'bundle_label' => ElasticsearchDataTypeDefinition::create('keyword'),
      'url_internal' => ElasticsearchDataTypeDefinition::create('keyword'),
      'url_alias' => ElasticsearchDataTypeDefinition::create('keyword'),
      'label' => ElasticsearchDataTypeDefinition::create('text'),
    ];
  }

}
