<?php

namespace Drupal\elasticsearch_helper;

use Drupal\Core\Config\ConfigFactory;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Elasticsearch\Client;
use Elasticsearch\ClientBuilder;

class ElasticsearchClientBuilder {

  /**
   * @var \Drupal\Core\Config\Config|\Drupal\Core\Config\ImmutableConfig
   */
  protected $config;

  /**
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  public function __construct(ConfigFactory $configFactory, ModuleHandlerInterface $moduleHandler) {
    $this->config = $configFactory->get('elasticsearch_helper.settings');
    $this->moduleHandler = $moduleHandler;
  }

  /**
   * Create an elasticsearch client.
   *
   * @return Client
   */
  public function build() {
    $clientBuilder = ClientBuilder::create();
    $clientBuilder->setHosts($this->getHosts());

    // Let other modules set their own handlers.
    $this->moduleHandler->alter('elasticsearch_helper_client_builder', $clientBuilder);

    return $clientBuilder->build();
  }

  /**
   * Get the hosts based on the site configuration.
   */
  protected function getHosts() {
    $host = implode(':', [
      $this->config->get('elasticsearch_helper.host'),
      $this->config->get('elasticsearch_helper.port')
    ]);

    if ($this->config->get('elasticsearch_helper.user')) {
      $credentials = implode(':', [
        $this->config->get('elasticsearch_helper.user'),
        $this->config->get('elasticsearch_helper.password')
      ]);

      if (!empty($credentials)) {
        $host = implode('@', [$credentials, $host]);
      }
    }

    return [$host];
  }
}
