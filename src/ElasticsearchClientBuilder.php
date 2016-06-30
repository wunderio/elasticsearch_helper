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
    $config = [];

    $host = implode(':', [
      $this->config->get('elasticsearch_helper.host'),
      $this->config->get('elasticsearch_helper.port')
    ]);

    // Use credentials if authentication is enabled.
    if ((int) $this->config->get('elasticsearch_helper.authentication')) {
      $credentials = implode(':', [
          $this->config->get('elasticsearch_helper.user'),
          $this->config->get('elasticsearch_helper.password')
      ]);

      if (!empty($credentials)) {
        $host = implode('@', [$credentials, $host]);
      }
    }

    if (!empty($host)) {
      $config['hosts'] = [$host];
    }

    return ClientBuilder::fromConfig($config);
  }
}
