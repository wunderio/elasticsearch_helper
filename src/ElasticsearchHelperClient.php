<?php

namespace Drupal\elasticsearch_helper;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Elasticsearch\ClientBuilder;

/**
 * Elasticsearch Helper Default Client.
 */
class ElasticsearchHelperClient implements ElasticsearchHelperClientInterface {

  /**
   * Drupal\Core\Config\Config|\Drupal\Core\Config\ImmutableConfig definition.
   *
   * @var \Drupal\Core\Config\Config|\Drupal\Core\Config\ImmutableConfig
   */
  protected $config;

  /**
   * Elasticsearch\Client definition.
   *
   * @var \Elasticsearch\Client
   */
  protected $client;

  /**
   * Drupal\Core\Extension\ModuleHandler definition.
   *
   * @var \Drupal\Core\Extension\ModuleHandler
   */
  protected $moduleHandler;

  /**
   * Constructs a new ElasticsearchHelperClient object.
   */
  public function __construct(ConfigFactoryInterface $config_factory, ModuleHandlerInterface $module_handler) {
    $this->config = $config_factory->get('elasticsearch_helper.settings');
    $this->moduleHandler = $module_handler;
    $this->client = $this->build();
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
    return $this->client->delete($parameters);
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
  public function indices(array $parameters = []) {
    return $this->client->indices()->get($parameters);
  }

  /**
   * {@inheritdoc}
   */
  public function createIndex(array $parameters) {
    $this->client->indices()->create($parameters);
  }

  /**
   * {@inheritdoc}
   */
  public function deleteIndex(array $parameters) {
    $this->client->indices()->delete($parameters);
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
