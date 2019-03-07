<?php

namespace Drupal\elasticsearch_helper_content;

use Drupal\Core\Config\ConfigFactoryInterface;

/**
 * Returns content index configuration.
 */
class ContentIndex implements ContentIndexInterface {

  /**
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * @var string
   */
  protected $configName = 'elasticsearch_helper_content.index';

  /**
   * IndexService constructor.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   */
  public function __construct(ConfigFactoryInterface $config_factory) {
    $this->configFactory = $config_factory;
  }

  /**
   * {@inheritdoc}
   */
  public function getIndexConfiguration() {
    // @todo Store configuration statically for better performance.
    return $this->configFactory->get($this->configName)->get();
  }

  /**
   * {@inheritdoc}
   */
  public function getBundleConfiguration($entity_type_id, $bundle) {
    $index_configuration = $this->getIndexConfiguration();

    if (isset($index_configuration[$entity_type_id][$bundle])) {
      return $index_configuration[$entity_type_id][$bundle];
    }

    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function getConfigName() {
    return $this->configName;
  }

}
