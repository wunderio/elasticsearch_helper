<?php

namespace Drupal\elasticsearch_helper;

use Drupal\Core\Config\ConfigFactory;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\elasticsearch_helper\Client\ClientBuilder;

/**
 * Class ElasticsearchClientBuilder.
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
    $host = implode(':', [
      $this->config->get('elasticsearch_helper.host'),
      $this->config->get('elasticsearch_helper.port'),
    ]);

    if ($this->config->get('elasticsearch_helper.user')) {
      $credentials = implode(':', [
        $this->config->get('elasticsearch_helper.user'),
        $this->config->get('elasticsearch_helper.password'),
      ]);

      if (!empty($credentials)) {
        $host = implode('@', [$credentials, $host]);
      }
    }

    if ($scheme = $this->config->get('elasticsearch_helper.scheme')) {
      $host = implode('://', [
        $scheme,
        $host,
      ]);
    }

    return [$host];
  }

  /**
   * Returns Elasticsearch client class name based on provided version.
   *
   * @return string|null
   */
  public static function getElasticsearchClientClassName() {
    $major_version = ElasticsearchClientVersion::getMajorVersion();

    if ($major_version >= 7) {
      return '\Drupal\elasticsearch_helper\Client\Version_7\Client';
    }
    elseif ($major_version >= 5) {
      return '\Elasticsearch\Client';
    }

    return NULL;
  }

}
