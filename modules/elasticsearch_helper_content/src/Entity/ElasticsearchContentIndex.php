<?php

namespace Drupal\elasticsearch_helper_content\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBase;
use Drupal\elasticsearch_helper_content\ElasticsearchContentIndexInterface;

/**
 * Defines the Elasticsearch content index entity.
 *
 * @ConfigEntityType(
 *   id = "elasticsearch_content_index",
 *   label = @Translation("Elasticsearch content index"),
 *   handlers = {
 *     "list_builder" = "Drupal\elasticsearch_helper_content\Controller\ElasticsearchContentIndexListBuilder",
 *     "form" = {
 *       "add" = "Drupal\elasticsearch_helper_content\Form\ElasticsearchContentIndexForm",
 *       "edit" = "Drupal\elasticsearch_helper_content\Form\ElasticsearchContentIndexForm",
 *       "delete" = "Drupal\elasticsearch_helper_content\Form\ElasticsearchContentIndexDeleteForm",
 *     }
 *   },
 *   config_prefix = "index",
 *   admin_permission = "administer site configuration",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label",
 *   },
 *   config_export = {
 *     "id",
 *     "label",
 *     "entity_type",
 *     "bundle",
 *     "index_name",
 *     "multilingual",
 *     "index_unpublished",
 *     "normalizer",
 *     "normalizer_configuration",
 *   },
 *   links = {
 *     "collection" = "/admin/config/search/elasticsearch_helper/content",
 *     "add-form" = "/admin/config/search/elasticsearch_helper/content/add",
 *     "edit-form" = "/admin/config/search/elasticsearch_helper/content/{elasticsearch_content_index}/edit",
 *     "delete-form" = "/admin/config/search/elasticsearch_helper/content/{elasticsearch_content_index}/delete",
 *   }
 * )
 */
class ElasticsearchContentIndex extends ConfigEntityBase implements ElasticsearchContentIndexInterface {

  /**
   * Indices that unpublished index should be indexed.
   */
  const INDEX_UNPUBLISHED = 1;

  /**
   * Indicates that unpublished content should not be indexed.
   */
  const INDEX_UNPUBLISHED_IGNORE = 0;

  /**
   * Indicates that publishing status is not available for entity type.
   */
  const INDEX_UNPUBLISHED_NA = -1;

  /**
   * Content index ID.
   *
   * @var string
   */
  protected $id;

  /**
   * Content index label.
   *
   * @var string
   */
  protected $label;

  /**
   * Index entity type.
   *
   * @var string
   */
  protected $entity_type;

  /**
   * Index bundle.
   *
   * @var string
   */
  protected $bundle;

  /**
   * Index name.
   *
   * @var string
   */
  protected $index_name;

  /**
   * @var bool
   */
  protected $multilingual = FALSE;

  /**
   * @var bool
   */
  protected $index_unpublished = self::INDEX_UNPUBLISHED_IGNORE;

  /**
   * Bundle normalizer plugin ID.
   *
   * @var string
   */
  protected $normalizer;

  /**
   * Bundle normalizer configuration.
   *
   * @var array
   */
  protected $normalizer_configuration = [];

  /**
   * {@inheritdoc}
   */
  public function getTargetEntityType() {
    return $this->entity_type;
  }

  /**
   * {@inheritdoc}
   */
  public function setTargetEntityType($entity_type) {
    $this->entity_type = $entity_type;
  }

  /**
   * {@inheritdoc}
   */
  public function getTargetBundle() {
    return $this->bundle;
  }

  /**
   * {@inheritdoc}
   */
  public function setTargetBundle($bundle) {
    $this->bundle = $bundle;
  }

  /**
   * {@inheritdoc}
   */
  public function getIndexName() {
    return $this->index_name;
  }

  /**
   * {@inheritdoc}
   */
  public function isMultilingual() {
    return (bool) $this->multilingual;
  }

  /**
   * {@inheritdoc}
   */
  public function indexUnpublishedContent() {
    return $this->index_unpublished;
  }

  /**
   * {@inheritdoc}
   */
  public function getNormalizer() {
    return $this->normalizer;
  }

  /**
   * {@inheritdoc}
   */
  public function setNormalizer($normalizer) {
    $this->normalizer = $normalizer;
  }

  /**
   * {@inheritdoc}
   */
  public function getNormalizerInstance() {
    $normalizer = $this->getNormalizer();

    $normalizer_configuration = [
      'entity_type' => $this->getTargetEntityType(),
      'bundle' => $this->getTargetBundle()
    ] + $this->getNormalizerConfiguration();

    return \Drupal::service('plugin.manager.elasticsearch_entity_normalizer')->createInstance($normalizer, $normalizer_configuration);
  }

  /**
   * {@inheritdoc}
   */
  public function getNormalizerConfiguration() {
    return $this->normalizer_configuration;
  }

  /**
   * {@inheritdoc}
   */
  public function setNormalizerConfiguration(array $configuration = []) {
    $this->normalizer_configuration = $configuration;
  }

}
