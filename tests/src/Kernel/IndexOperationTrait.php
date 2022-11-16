<?php

namespace Drupal\Tests\elasticsearch_helper\Kernel;

use Drupal\elasticsearch_helper\Elasticsearch\Index\FieldDefinition;
use Drupal\elasticsearch_helper\Elasticsearch\Index\MappingDefinition;

/**
 * Elasticsearch index operation trait.
 */
trait IndexOperationTrait {

  /**
   * Defines multilingual node index prefix.
   */
  protected $multilingualNodeIndexPrefix = 'test-multilingual-node-index-';

  /**
   * An HTTP request with curl.
   *
   * @param string $path
   *   The request path.
   * @param string $method
   *   The request method.
   * @param array $headers
   *   The headers array.
   * @param string $body
   *   The body of the request.
   *
   * @return array
   *   The decoded response.
   */
  protected function httpRequest($path, $method = 'GET', array $headers = [], $body = NULL) {
    $path = ltrim($path, '/');
    $host = $this->getHost();
    $url = sprintf('%s://%s:%d/%s', $this->getScheme(), $host['host'], $host['port'], $path);

    // Query elasticsearch.
    // Use Curl for now because http client middleware fails in KernelTests
    // (See: https://www.drupal.org/project/drupal/issues/2571475)
    $curl = curl_init();
    curl_setopt($curl, CURLOPT_URL, $url);
    curl_setopt($curl, CURLOPT_CUSTOMREQUEST, $method);
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);

    if ($headers) {
      curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
    }

    if ($basic_auth = $this->getBasicAuth()) {
      curl_setopt($curl, CURLOPT_USERPWD, sprintf('%s:%s', $basic_auth['user'], $basic_auth['password']));
    }

    if ($body) {
      curl_setopt($curl, CURLOPT_POSTFIELDS, $body);
    }

    $json = curl_exec($curl);

    return json_decode($json, TRUE);
  }

  /**
   * Sets Elasticsearch configuration.
   *
   * @return void
   */
  protected function setElasticsearchHelperConfiguration() {
    $settings = $this->config('elasticsearch_helper.settings');

    $settings->set('scheme', getenv('ELASTICSEARCH_HELPER_TEST_SCHEME') ?: 'http');
    $settings->set('hosts', [
      [
        'host' => getenv('ELASTICSEARCH_HELPER_TEST_HOST') ?: 'localhost',
        'port' => getenv('ELASTICSEARCH_HELPER_TEST_PORT') ?: '9200',
      ]
    ]);
    $settings->set('authentication.method', 'basic_auth');
    $settings->set('authentication.configuration.basic_auth', [
      'user' => getenv('ELASTICSEARCH_HELPER_TEST_BASIC_AUTH_USER') ?: NULL,
      'password' => getenv('ELASTICSEARCH_HELPER_TEST_BASIC_AUTH_PASSWORD') ?: NULL,
    ]);
    $settings->set('ssl', [
      'certificate' => getenv('ELASTICSEARCH_HELPER_TEST_SSL_CERTIFICATE') ?: NULL,
      'skip_verification' => getenv('ELASTICSEARCH_HELPER_TEST_SSL_SKIP_VERIFICATION') ?: NULL,
    ]);

    // Save the config.
    $settings->save();
    // Clear static cache.
    $this->container->get('config.factory')->clearStaticCache();
  }

  /**
   * Returns an array with a host and a port.
   *
   * @return array
   */
  protected function getHost() {
    return $this->config('elasticsearch_helper.settings')->get('hosts')[0] ?? [
      'host' => NULL,
      'port' => NULL,
    ];
  }

  /**
   * Returns an array with basic auth credentials.
   *
   * @return array
   */
  protected function getBasicAuth() {
    return $this->config('elasticsearch_helper.settings')->get('authentication.configuration.basic_auth') ?? [];
  }

  /**
   * Returns URI scheme.
   *
   * @return string
   */
  protected function getScheme() {
    return $this->config('elasticsearch_helper.settings')->get('scheme');
  }

  /**
   * Returns a list of mapping definitions keyed by index name.
   *
   * @return MappingDefinition[]
   */
  protected function getMappingDefinitions() {
    $result = [];

    $result['test-simple-node-index'] = MappingDefinition::create()
      ->addProperty('id', FieldDefinition::create('integer'))
      ->addProperty('uuid', FieldDefinition::create('keyword'))
      ->addProperty('title', FieldDefinition::create('text'))
      ->addProperty('status', FieldDefinition::create('boolean'));

    $multilingual_mapping_definition = MappingDefinition::create()
      ->addProperty('id', FieldDefinition::create('integer'))
      ->addProperty('uuid', FieldDefinition::create('keyword'))
      ->addProperty('title', FieldDefinition::create('text'))
      ->addProperty('status', FieldDefinition::create('boolean'))
      ->addProperty('langcode', FieldDefinition::create('keyword'));

    foreach (['en', 'lv'] as $langcode) {
      $result[$this->multilingualNodeIndexPrefix . $langcode] = $multilingual_mapping_definition;
    }

    return $result;
  }

  /**
   * Removes Elasticsearch indices used for testing purposes.
   *
   * @return array[]
   *   A list of responses.
   */
  protected function removeIndices() {
    $responses = [];

    foreach (array_keys($this->getMappingDefinitions()) as $index_name) {
      // Remove the index.
      $responses[] = $this->httpRequest($index_name, 'DELETE');
    }

    return $responses;
  }

  /**
   * Creates Elasticsearch indices for testing purposes.
   *
   * @return array[]
   *   A list of responses.
   */
  protected function createIndices() {
    $responses = [];

    foreach ($this->getMappingDefinitions() as $index_name => $mapping_definition) {
      // Create the index.
      $responses[] = $this->createIndex($index_name, $mapping_definition);
    }

    return $responses;
  }

  /**
   * Creates index mapping.
   *
   * @param $index_name
   * @param \Drupal\elasticsearch_helper\Elasticsearch\Index\MappingDefinition $mapping_definition
   *
   * @return array
   */
  protected function createIndex($index_name, MappingDefinition $mapping_definition) {
    // Put mapping.
    return $this->httpRequest(
      $index_name,
      'PUT',
      ['Content-Type: application/json'],
      sprintf('{"mappings": %s}', $mapping_definition->asString())
    );
  }

  /**
   * Returns multilingual node index name (per language).
   *
   * @param $langcode
   *
   * @return string
   */
  protected function getMultilingualNodeIndexName($langcode) {
    return $this->multilingualNodeIndexPrefix . $langcode;
  }

  /**
   * Returns simple node index name.
   *
   * @return string
   */
  protected function getSimpleNodeIndexName() {
    return 'test-simple-node-index';
  }

}
