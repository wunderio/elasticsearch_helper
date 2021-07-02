<?php

namespace Drupal\elasticsearch_helper_example;

use Drupal\elasticsearch_helper\ClientInterface;
use GuzzleHttp\Client;
use GuzzleHttp\RequestOptions;

/**
 * Elasticsearch Helper Custom Client.
 *
 * This is only an example and does not actually have a real api to connect to.
 */
class CustomClient implements ClientInterface {

  /**
   * Drupal\Core\Config\Config|\Drupal\Core\Config\ImmutableConfig definition.
   *
   * @var \Drupal\Core\Config\Config|\Drupal\Core\Config\ImmutableConfig
   */
  protected $config;

  /**
   * GuzzleHttp\Client definition.
   *
   * @var \GuzzleHttp\Client
   */
  protected $client;

  /**
   * Constructs a new CustomClient object.
   */
  public function __construct() {
    $this->client = $this->build();
  }

  /**
   * Create an elasticsearch client.
   */
  public function build() {
    // Initialize the custom client here.
    $token = 'SomeToken';
    $base_url = 'http://localhost/api/custom';

    $clientConfig = [
      'base_uri' => $base_url,
      RequestOptions::HEADERS =>
        [
          'Authorization' => "Basic {$token}",
        ],

    ];

    $this->client = new Client($clientConfig);
  }

  /**
   * {@inheritdoc}
   */
  public function index(array $parameters) {
    $this->client->post('index', [
      RequestOptions::JSON => $parameters,
    ]);
  }

  /**
   * {@inheritdoc}
   */
  public function update(array $parameters) {
    $this->client->post('update', [
      RequestOptions::JSON => $parameters,
    ]);
  }

  /**
   * {@inheritdoc}
   */
  public function delete(array $parameters) {
    $this->client->post('delete', [
      RequestOptions::JSON => $parameters,
    ]);
  }

  /**
   * {@inheritdoc}
   */
  public function search(array $parameters) {
    $this->client->post('search', [
      RequestOptions::JSON => $parameters,
    ]);
  }

  /**
   * {@inheritdoc}
   */
  public function msearch(array $parameters) {
    $this->client->post('msearch', [
      RequestOptions::JSON => $parameters,
    ]);
  }

  /**
   * {@inheritdoc}
   */
  public function bulk(array $parameters) {
    $this->client->post('bulk', [
      RequestOptions::JSON => $parameters,
    ]);
  }

  /**
   * {@inheritdoc}
   */
  public function indices(array $parameters = []) {
    $this->client->post('get_indices', [
      RequestOptions::JSON => $parameters,
    ]);
  }

  /**
   * {@inheritdoc}
   */
  public function createIndex(array $parameters) {
    $this->client->post('create_index', [
      RequestOptions::JSON => $parameters,
    ]);
  }

  /**
   * {@inheritdoc}
   */
  public function deleteIndex(array $parameters) {
    $this->client->post('delete_index', [
      RequestOptions::JSON => $parameters,
    ]);
  }

  /**
   * {@inheritdoc}
   */
  public function health() {
    $this->client->post('health');

  }

  /**
   * {@inheritdoc}
   */
  public function indexExists($name) {
    $this->client->post('index_exists/' . $name);
  }

  /**
   * {@inheritdoc}
   */
  public function templateExists($name) {
    $this->client->post('template/' . $name);
  }

  /**
   * {@inheritdoc}
   */
  public function putTemplate(array $parameters) {
    $this->client->post('put_template', [
      RequestOptions::JSON => $parameters,
    ]);
  }

}
