<?php

namespace Drupal\elasticsearch_helper_content\Plugin\Deriver;

use Drupal\Core\Entity\EntityTypeBundleInfoInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Plugin\Discovery\ContainerDeriverInterface;
use Drupal\elasticsearch_helper_content\ContentIndexInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class ContentIndexDeriver
 */
class ContentIndexDeriver implements ContainerDeriverInterface {

  /**
   * @var \Drupal\elasticsearch_helper_content\ContentIndexInterface
   */
  protected $contentIndex;

  /**
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * @var \Drupal\Core\Entity\EntityTypeBundleInfoInterface
   */
  protected $entityTypeBundleInfo;

  /**
   * List of derivative definitions.
   *
   * @var array
   */
  protected $derivatives;

  /**
   * ContentIndexDeriver constructor.
   *
   * @param \Drupal\elasticsearch_helper_content\ContentIndexInterface $content_index
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   * @param \Drupal\Core\Entity\EntityTypeBundleInfoInterface $entity_type_bundle_info
   */
  public function __construct(ContentIndexInterface $content_index, EntityTypeManagerInterface $entity_type_manager, EntityTypeBundleInfoInterface $entity_type_bundle_info) {
    $this->contentIndex = $content_index;
    $this->entityTypeManager = $entity_type_manager;
    $this->entityTypeBundleInfo = $entity_type_bundle_info;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, $base_plugin_id) {
    return new static(
      $container->get('elasticsearch_helper_content.content_index'),
      $container->get('entity_type.manager'),
      $container->get('entity_type.bundle.info')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getDerivativeDefinition($derivative_id, $base_plugin_definition) {
    if (!isset($this->derivatives)) {
      $this->getDerivativeDefinitions($base_plugin_definition);
    }
    if (isset($this->derivatives[$derivative_id])) {
      return $this->derivatives[$derivative_id];
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getDerivativeDefinitions($base_plugin_definition) {
    if (!isset($this->derivatives)) {
      // Get index configuration.
      $index_configuration = $this->contentIndex->getIndexConfiguration();

      // Loop over entity types.
      foreach ($index_configuration as $entity_type_id => $entity_type_configuration) {
        $bundle_info = $this->entityTypeBundleInfo->getBundleInfo($entity_type_id);

        // Loop over bundle configurations.
        foreach ($entity_type_configuration as $bundle => $bundle_configuration) {
          try {
            // Get bundle normalizer definition.
            $normalizer = $bundle_configuration['normalizer'];

            // Prepare derivative ID.
            $derivative_id = sprintf('%s_%s', $entity_type_id, $bundle);

            $this->derivatives[$derivative_id] = [
              // When derivative creates a definition, the base plugin
              // definition ID is followed by a colon (:) and derivative ID.
              'id' => sprintf('%s:%s', $base_plugin_definition['id'], $derivative_id),
              'label' => t('@entity_type_label - @bundle_label @normalizer index', [
                '@entity_type_label' => $this->entityTypeManager->getDefinition($entity_type_id)->getLabel(),
                '@bundle_label' => $bundle_info[$bundle]['label'],
                '@normalizer' => $normalizer,
              ]),
              'class' => $base_plugin_definition['class'],
              'indexName' => str_replace('_', '-', "{$entity_type_id}-{$bundle}"),
              'typeName' => $bundle,
              'entityType' => $entity_type_id,
              'bundle' => $bundle,
              // Store bundle configuration.
              'configuration' => $bundle_configuration,
            ];
          } catch (\Exception $e) {
          }
        }
      }
    }

    return $this->derivatives;
  }

}
