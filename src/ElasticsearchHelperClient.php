<?php

namespace Drupal\elasticsearch_helper;

use Drupal\Core\Config\ConfigFactoryInterface;
use Elasticsearch\ClientBuilder;

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
   * Elasticsearch\Client definition.
   *
   * @var \Elasticsearch\Client
   */
  protected $client;

  /**
   * Constructs a new ElasticsearchHelperClient object.
   */
  public function __construct(ConfigFactoryInterface $config_factory) {
    $this->configFactory = $config_factory;
  }

  /**
   * Create an elasticsearch client.
   *
   * @return \Elasticsearch\Client
   *   Return the client.
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

  /**
   * {@inheritdoc}
   */
  public function index(array $parameters) {
    return $this->client->index($parameters);
  }

  /**
   * {@inheritdoc}
   */
  public function update(array $parameters) {
    return $this->client->update($parameters);
  }

  /**
   * {@inheritdoc}
   */
  public function delete(array $parameters) {
    return $this->client->update($parameters);
  }

  /**
   * {@inheritdoc}
   */
  public function search(array $parameters) {
    return $this->client->search($parameters);
  }

  /**
   * {@inheritdoc}
   */
  public function msearch(array $parameters) {
    return $this->client->msearch($parameters);
  }

  /**
   * {@inheritdoc}
   */
  public function bulk(array $parameters) {
    return $this->client->bulk($parameters);
  }

  /**
   * {@inheritdoc}
   */
  public function indices() {
    return $this->client->indices()->get();
  }

  /**
   * {@inheritdoc}
   */
  public function health() {
    return $this->client->cluster()->health();

  }

  /**
   * {@inheritdoc}
   */
  public function indexExists($name) {
    return $this->client->indices()->exists(['index' => $name]);
  }

  /**
   * {@inheritdoc}
   */
  public function templateExists($name) {
    return $this->client->indices()->existsTemplate(['name' => $name]);
  }

  /**
   * {@inheritdoc}
   */
  public function putTemplate(array $parameters) {
    return $this->client->indices()->putTemplate($parameters);
  }

}