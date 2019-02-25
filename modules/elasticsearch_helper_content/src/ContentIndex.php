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
  public function getConfiguration() {
    return $this->configFactory->get($this->configName)->get();
  }

  /**
   * {@inheritdoc}
   */
  public function getConfigName() {
    return $this->configName;
  }

}
