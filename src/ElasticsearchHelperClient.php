<?php

namespace Drupal\elasticsearch_helper;

use Drupal\Core\Config\ConfigFactoryInterface;

/**
 * Elasticsearch Helper Default Client.
 */
class ElasticsearchHelperClient implements ElasticsearchHelperClientInterface {

  /**
   * Drupal\Core\Config\ConfigFactoryInterface definition.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * Constructs a new ElasticsearchHelperClient object.
   */
  public function __construct(ConfigFactoryInterface $config_factory) {
    $this->configFactory = $config_factory;
  }

}
