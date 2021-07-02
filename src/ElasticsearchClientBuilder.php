<?php

namespace Drupal\elasticsearch_helper;

use Drupal\Core\Config\ConfigFactory;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Elasticsearch\ClientBuilder;

/**
 * Class ElasticsearchClientBuilder.
 *
 * @deprecated
 *
 * @package Drupal\elasticsearch_helper
 */
class ElasticsearchClientBuilder {

  /**
   * @var \Drupal\Core\Config\Config|\Drupal\Core\Config\ImmutableConfig
   */
  protected $config;

  /**
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * ElasticsearchClientBuilder constructor.
   * @param \Drupal\Core\Config\ConfigFactory $configFactory
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $moduleHandler
   */
  public function __construct(ConfigFactory $configFactory, ModuleHandlerInterface $moduleHandler) {
    $this->config = $configFactory->get('elasticsearch_helper.settings');
    $this->moduleHandler = $moduleHandler;
  }

  /**
   * Create an elasticsearch client.
   *
   * @return \Elasticsearch\Client
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
    $hosts = [];

    foreach ($this->config->get('hosts') as $host_config) {
      $host = ElasticsearchHost::createFromArray($host_config);

      $host_entry = [
        'host' => $host->getHost(),
        'port' => $host->getPort(),
        'scheme' => $host->getScheme(),
      ];

      if ($host->isAuthEnabled()) {
        $host_entry['user'] = $host->getAuthUsername();
        $host_entry['pass'] = $host->getAuthPassword();
      }

      // Use only explicitly defined configuration.
      $hosts[] = array_filter($host_entry);
    }

    return $hosts;
  }

}
