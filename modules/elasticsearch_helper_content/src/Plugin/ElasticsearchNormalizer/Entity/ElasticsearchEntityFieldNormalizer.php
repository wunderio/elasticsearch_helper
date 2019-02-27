<?php

namespace Drupal\elasticsearch_helper_content\Plugin\ElasticsearchNormalizer\Entity;

use Drupal\Core\Entity\EntityTypeBundleInfoInterface;
use Drupal\elasticsearch_helper_content\ElasticsearchEntityFieldNormalizerInterface;
use Drupal\elasticsearch_helper_content\ElasticsearchEntityNormalizerBase;
use Drupal\elasticsearch_helper_content\ElasticsearchFieldNormalizerManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * @ElasticsearchEntityNormalizer(
 *   id = "field",
 *   label = @Translation("Content entity field"),
 *   weight = 5
 * )
 */
class ElasticsearchEntityFieldNormalizer extends ElasticsearchEntityNormalizerBase implements ElasticsearchEntityFieldNormalizerInterface {

  /**
   * @var \Drupal\elasticsearch_helper_content\ElasticsearchFieldNormalizerManagerInterface
   */
  protected $elasticsearchFieldNormalizerManager;

  /**
   * ElasticsearchEntityFieldNormalizer constructor.
   *
   * @param array $configuration
   * @param $plugin_id
   * @param $plugin_definition
   * @param \Drupal\Core\Entity\EntityTypeBundleInfoInterface $entity_type_bundle_info
   * @param \Drupal\elasticsearch_helper_content\ElasticsearchFieldNormalizerManagerInterface $elasticsearch_field_normalizer_manager
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, EntityTypeBundleInfoInterface $entity_type_bundle_info, ElasticsearchFieldNormalizerManagerInterface $elasticsearch_field_normalizer_manager) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $entity_type_bundle_info);

    $this->elasticsearchFieldNormalizerManager = $elasticsearch_field_normalizer_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity_type.bundle.info'),
      $container->get('plugin.manager.elasticsearch_field_normalizer')
    );
  }

  /**
   * Returns a list of field normalizer instances.
   *
   * @return \Drupal\elasticsearch_helper_content\ElasticsearchNormalizerInterface[]
   */
  protected function getFieldNormalizerInstances() {
    $instances = [];

    if (isset($this->configuration['fields'])) {
      foreach ($this->configuration['fields'] as $field_name => $field_configuration) {
        try {
          $instances[$field_name] = $this->elasticsearchFieldNormalizerManager->createInstance($field_configuration['normalizer']);
        } catch (\Exception $e) {
          watchdog_exception('elasticsearch_helper_content', $e);
        }
      }
    }

    return $instances;
  }

  /**
   * {@inheritdoc}
   */
  public function normalize($object, array $context = []) {
    $data = parent::normalize($object, $context);

    foreach ($this->getFieldNormalizerInstances() as $field_name => $field_normalizer_instance) {
      if ($object->hasField($field_name)) {
        $data[$field_name] = $field_normalizer_instance->normalize($object->get($field_name), $context);
      }
    }

    return $data;
  }

  /**
   * {@inheritdoc}
   */
  public function getPropertyDefinitions() {
    // Get core property definitions.
    $core_property_definitions = $this->getCorePropertyDefinitions();

    // Prepare property (field) definitions.
    $property_definitions = [];

    foreach ($this->getFieldNormalizerInstances() as $field_name => $field_normalizer_instance) {
      $property_definitions[$field_name] = $field_normalizer_instance->getPropertyDefinitions();
    }

    return array_merge($core_property_definitions, $property_definitions);
  }

}
