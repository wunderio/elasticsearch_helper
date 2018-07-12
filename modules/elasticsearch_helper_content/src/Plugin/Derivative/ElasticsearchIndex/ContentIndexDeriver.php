<?php

namespace Drupal\elasticsearch_helper_content\Plugin\Derivative\ElasticsearchIndex;

use Drupal\Component\Plugin\Derivative\DeriverBase;

class ContentIndexDeriver extends DeriverBase /* for any DI: implements ContainerDeriverInterface */ {

  const DEFAULT_INDEX_NAME = '_default';

  /**
   * {@inheritdoc}
   */
  public function getDerivativeDefinitions($base_plugin_definition) {
    // Derive an index plugin per intended index.
    // For array structure see getIntendedIndexes().
    foreach ($this->getIntendedIndexes() as $entity_type_id => $indexes) {
      // Append default index if necessary.
      // (i.e. in case no explicit indices are set for that entity type).
      $indexes = count($indexes) ? $indexes : [self::DEFAULT_INDEX_NAME => []];
      foreach ($indexes as $index_name => $bundles) {
        // Get base plugin definition
        $index = $base_plugin_definition;

        // Assemble specific index id.
        $id = [$index['id'], $entity_type_id];
        if ($index_name != self::DEFAULT_INDEX_NAME) {
          $id[] = $index_name;
        }
        $id = implode('_', $id);


        // Set plugin properties.
        $index['id'] = $id;
        $index['indexName'] = str_replace('_', '-', $id) . '-{langcode}';
        $index['label'] = "Content Index for $entity_type_id"
                           . (empty($bundles) ? '' : ' type(s) ' . implode(', ', $bundles));

        $index['entityType'] = $entity_type_id;
        $index['typeName'] = $index['entityType'];

        // Set bundle restrictions.
        if (!empty($bundles)) {
          // Reduce just one bundle from array to its only one item.
          $bundles = (count($bundles) > 1) ? $bundles : reset($bundles);
          // @Todo More than one bundle requires https://trello.com/c/n6X3NUA0.
          $index['bundle'] = $bundles;
        }

        $this->derivatives[$id] = $index;
      }
    }

    return $this->derivatives;
  }

  /**
   * @return array
   *   A structured array with intended indices like:
   *      'node' => [
   *        '_default' => [],
   *        'indexname' => ['bundle_id'],
   *      ],
   *      'entity_type' => [],
   *    ];
   *
   */
  public function getIntendedIndexes() {
    $bundles = [
      'node' => [
        self::DEFAULT_INDEX_NAME => [],
        'movie' => ['movie'],
      ],
      'taxonomy_term' => [],
      'user' => [],
    ];

    return $bundles;
  }
}
