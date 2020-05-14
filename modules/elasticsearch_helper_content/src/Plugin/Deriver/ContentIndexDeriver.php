<?php

namespace Drupal\elasticsearch_helper_content\Plugin\Deriver;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Plugin\Discovery\ContainerDeriverInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class ContentIndexDeriver
 */
class ContentIndexDeriver implements ContainerDeriverInterface {

  /**
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * List of derivative definitions.
   *
   * @var array
   */
  protected $derivatives;

  /**
   * ContentIndexDeriver constructor.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager) {
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, $base_plugin_id) {
    return new static(
      $container->get('entity_type.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getDerivativeDefinition($derivative_id, $base_plugin_definition) {
    $derivatives = $this->getDerivativeDefinitions($base_plugin_definition);

    return isset($derivatives[$derivative_id]) ? $derivatives[$derivative_id] : [];
  }

  /**
   * {@inheritdoc}
   */
  public function getDerivativeDefinitions($base_plugin_definition) {
    if (!isset($this->derivatives)) {
      $this->derivatives = [];

      try {
        /** @var \Drupal\elasticsearch_helper_content\ElasticsearchContentIndexInterface[] $index_entities */
        $index_entities = $this->entityTypeManager->getStorage('elasticsearch_content_index')->loadMultiple();

        // Loop over index entities.
        foreach ($index_entities as $index_entity) {
          $entity_type_id = $index_entity->getTargetEntityType();
          $bundle = $index_entity->getTargetBundle();

          // Prepare derivative ID.
          $derivative_id = $index_entity->id();

          $this->derivatives[$derivative_id] = [
            // When derivative creates a definition, the base plugin
            // definition ID is followed by a colon (:) and derivative ID.
            'id' => sprintf('%s:%s', $base_plugin_definition['id'], $derivative_id),
            'label' => $index_entity->label(),
            'class' => $base_plugin_definition['class'],
            'indexName' => $index_entity->getIndexName(),
            'typeName' => $bundle,
            'entityType' => $entity_type_id,
            'bundle' => $bundle,
            'index_entity_id' => $index_entity->id(),
            // Expose content index plugin configuration in Elasticsearch index
            // plugin definition. This allows other modules to create indices
            // dynamically.
            'multilingual' => $index_entity->isMultilingual(),
          ];
        }
      }
      catch (\Exception $e) {
        watchdog_exception('elasticsearch_helper_content', $e);
      }
    }

    return $this->derivatives;
  }

}
