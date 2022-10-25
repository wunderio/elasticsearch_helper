<?php

namespace Drupal\Tests\elasticsearch_helper\Kernel;

use Drupal\elasticsearch_helper\Elasticsearch\Index\FieldDefinition;
use Drupal\elasticsearch_helper\Elasticsearch\Index\MappingDefinition;
use Drupal\elasticsearch_helper\ElasticsearchHost;

/**
 * Elasticsearch index operation trait.
 */
trait IndexOperationTrait {

  /**
   * Defines multilingual node index prefix.
   */
  protected $multilingualNodeIndexPrefix = 'elasticsearch_helper_test_node_index-';

  /**
   * HTTP request with curl.
   *
   * @param string $uri
   *   The request uri
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
  protected function httpRequest($uri, $method = 'GET', array $headers = [], $body = NULL) {
    $host = $this->getHost();
    $uri = ltrim($uri, '/');
    $uri = sprintf('http://%s:%d/%s', $host->getHost(), $host->getPort(), $uri);

    // Query elasticsearch.
    // Use Curl for now because http client middleware fails in KernelTests
    // (See: https://www.drupal.org/project/drupal/issues/2571475)
    $curl = curl_init();
    curl_setopt($curl, CURLOPT_URL, $uri);
    curl_setopt($curl, CURLOPT_CUSTOMREQUEST, $method);
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);

    if ($headers) {
      curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
    }

    if ($body) {
      curl_setopt($curl, CURLOPT_POSTFIELDS, $body);
    }

    $json = curl_exec($curl);

    return json_decode($json, TRUE);
  }

  /**
   * Returns Elasticsearch host definition.
   *
   * @return \Drupal\elasticsearch_helper\ElasticsearchHost
   */
  protected function getHost() {
    $host = $this->config('elasticsearch_helper.settings')->get('hosts')[0];

    return ElasticsearchHost::createFromArray($host);
  }

  /**
   * Returns a list of mapping definitions keyed by index name.
   *
   * @return MappingDefinition[]
   */
  protected function getMappingDefinitions() {
    $result = [];

    $result['elasticsearch_helper_test_simple_node_index'] = MappingDefinition::create()
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
   * @param $mapping_definition
   *
   * @return array
   */
  protected function createIndex($index_name, $mapping_definition) {
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
    return 'elasticsearch_helper_test_simple_node_index';
  }

}
