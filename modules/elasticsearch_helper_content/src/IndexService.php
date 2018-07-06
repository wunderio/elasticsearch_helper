<?php

namespace Drupal\elasticsearch_helper_content;
use Drupal\Core\Config\ConfigFactoryInterface;

/**
 * Returns index configuration.
 */
class IndexService implements IndexServiceInterface {

  /**
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * ElasticsearchHelperContentIndexService constructor.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   */
  public function __construct(ConfigFactoryInterface $config_factory) {
    $this->configFactory = $config_factory;
  }

  /**
   * {@inheritdoc}
   */
  public function getBundleConfiguration($entity_type, $bundle) {
    if ($configuration = $this->configFactory->get('elasticsearch_helper_content.index')) {
      if ($bundle_configuration = $configuration->get($entity_type . '.' . $bundle)) {
        return $bundle_configuration;
      }
    }

    return [];
  }

}
