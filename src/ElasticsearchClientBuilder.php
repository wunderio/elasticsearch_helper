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
   * Create an elasticsearch client.
   *
   * @return \Elasticsearch\Client
   */
  public function build() {
    // Get Elasticsearch connection settings.
    $connection = ElasticsearchConnectionSettings::createFromArray($this->config->getRawData());

    $clientBuilder = ClientBuilder::create();
    $clientBuilder->setHosts($connection->getFormattedHosts());

    // Set basic auth credentials.
    if ($username = $connection->getBasicAuthUser()) {
      $password = $connection->getBasicAuthPassword();
      $clientBuilder->setBasicAuthentication($username, $password);
    }

    // Set API key.
    if (($api_key_id = $connection->getApiKeyId()) && ($api_key = $connection->getApiKey())) {
      $clientBuilder->setApiKey($api_key_id, $api_key);
    }

    // Use SSL certificate if available.
    if ($certificate = $connection->getSslCertificate()) {
      $clientBuilder->setSSLCert($certificate);
    }

    // Skip SSL certificate verification if needed.
    if ($connection->skipSslVerification()) {
      $clientBuilder->setSSLVerification(FALSE);
    }

    // Let other modules set their own handlers.
    $this->moduleHandler->alter('elasticsearch_helper_client_builder', $clientBuilder);

    return $clientBuilder->build();
  }

}
