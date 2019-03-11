<?php

namespace Drupal\elasticsearch_helper_content\Controller;

use Drupal\Core\Config\Entity\ConfigEntityListBuilder;
use Drupal\Core\Entity\EntityInterface;

/**
 * Provides a listing of Elasticsearch content indices.
 */
class ElasticsearchContentIndexListBuilder extends ConfigEntityListBuilder {

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $header['label'] = $this->t('Label');
    $header['entity_type'] = $this->t('Entity type');
    $header['bundle'] = $this->t('Bundle');
    $header['id'] = $this->t('Machine name');

    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   *
   * @param \Drupal\elasticsearch_helper_content\ElasticsearchContentIndexInterface $entity
   */
  public function buildRow(EntityInterface $entity) {
    $row['label'] = $entity->label();
    $row['entity_type'] = $entity->getTargetEntityType();
    $row['bundle'] = $entity->getTargetBundle();
    $row['id'] = $entity->id();

    return $row + parent::buildRow($entity);
  }

}
