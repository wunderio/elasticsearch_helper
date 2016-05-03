<?php

namespace Drupal\elasticsearch_helper;

use Drupal\Core\Config\ConfigFactory;
use Elasticsearch\Client;
use Elasticsearch\ClientBuilder;

class ElasticsearchClientBuilder {

  /**
   * @var \Drupal\Core\Config\Config|\Drupal\Core\Config\ImmutableConfig
   */
  protected $config;

  public function __construct(ConfigFactory $configFactory) {
    $this->config = $configFactory->get('elasticsearch_helper.settings');
  }

  /**
   * Create an elasticsearch client.
   *
   * @return Client
   */
  public function build() {
    return ClientBuilder::fromConfig([
      'hosts' => $this->config->get('hosts'),
    ]);
  }
}