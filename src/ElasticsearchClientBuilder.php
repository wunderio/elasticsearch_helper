<?php

namespace Drupal\elasticsearch_helper;

use Drupal\Core\Config\ConfigFactory;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Elasticsearch\ClientBuilder;

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
   * Builds an Elasticsearch client instance.
   *
   * @return \Elasticsearch\Client
   */
  public function build() {
    // Get Elasticsearch connection settings. Raw configuration data cannot be
    // used here as settings might be overridden.
    $connection = new ElasticsearchConnectionSettings(
      $this->config->get('hosts') ?: [],
      $this->config->get('scheme') ?: NULL,
      $this->config->get('authentication') ?: [],
      $this->config->get('ssl') ?: []
    );

    $client_builder = ClientBuilder::create();
    $client_builder->setHosts($connection->getFormattedHosts());

    // Apply authentication method configuration.
    if ($auth_method_instance = $connection->getAuthMethodInstance()) {
      $auth_method_instance->authenticate($client_builder);
    }

    // Use SSL certificate if available.
    if ($certificate = $connection->getSslCertificate()) {
      $client_builder->setSSLVerification($certificate);
    }

    // Skip SSL certificate verification if needed.
    if ($connection->skipSslVerification()) {
      $client_builder->setSSLVerification(FALSE);
    }

    // Let other modules set their own handlers.
    $this->moduleHandler->alter('elasticsearch_helper_client_builder', $client_builder);

    return $client_builder->build();
  }

}
