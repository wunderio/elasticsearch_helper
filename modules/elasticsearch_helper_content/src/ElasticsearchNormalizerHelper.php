<?php

namespace Drupal\elasticsearch_helper_content;

use Drupal\Core\Entity\EntityDisplayRepositoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;

/**
 * Class ElasticsearchNormalizerHelper
 */
class ElasticsearchNormalizerHelper {

  /**
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * @var \Drupal\Core\Entity\EntityDisplayRepositoryInterface
   */
  protected $entityDisplayRepository;

  public function __construct(EntityTypeManagerInterface $entity_type_manager, EntityDisplayRepositoryInterface $entity_display_repository) {
    $this->entityTypeManager = $entity_type_manager;
    $this->entityDisplayRepository = $entity_display_repository;
  }

  /**
   * Returns a list of enabled view modes.
   *
   * @return array
   */
  public function getEntityViewDisplayOptions($entity_type = NULL, $bundle = NULL) {
    $view_modes = [];

    try {
      // Get all view modes.
      $view_modes = $this->entityDisplayRepository->getViewModes($entity_type);

      // Get entity view display IDs (enabled view modes).
      $entity_view_display_storage = $this->entityTypeManager->getStorage('entity_view_display');
      $query = $entity_view_display_storage->getQuery()
        ->condition('targetEntityType', $entity_type)
        ->condition('bundle', $bundle)
        ->condition('status', TRUE);

      // Get the view modes from retrieved entity view displays.
      $enabled_view_mode_ids = [];
      if ($entity_view_display_result = $query->execute()) {
        $enabled_view_mode_ids = array_map(function ($entity) {
          /** @var \Drupal\Core\Entity\Entity\EntityViewDisplay $entity */
          return $entity->getMode();
        }, $entity_view_display_storage->loadMultiple($entity_view_display_result));
      }

      // Filter out view mode that are not enabled.
      $view_modes = array_intersect_key($view_modes, array_flip($enabled_view_mode_ids));

      // Get view mode labels.
      $view_modes = array_map(function ($view_mode) {
        return $view_mode['label'];
      }, $view_modes);
    }
    catch (\Exception $e) {
      watchdog_exception('elasticsearch_helper_content', $e);
    }

    return ['default' => t('Default')] + $view_modes;
  }

}
