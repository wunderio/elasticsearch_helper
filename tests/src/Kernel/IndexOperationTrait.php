<?php

namespace Drupal\Tests\elasticsearch_helper\Kernel;

/**
 * Elasticsearch index operation trait.
 */
trait IndexOperationTrait {

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

    fwrite(STDERR, print_r($url, TRUE));

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

    curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, $this->verifySslCertificate());

    $json = curl_exec($curl);

    fwrite(STDERR, print_r($json, TRUE));

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

    $basic_auth_user = getenv('ELASTICSEARCH_HELPER_TEST_BASIC_AUTH_USER') ?: '';

    if ($basic_auth_user) {
      $settings->set('authentication.method', 'basic_auth');
      $settings->set('authentication.configuration.basic_auth', [
        'user' => $basic_auth_user,
        'password' => getenv('ELASTICSEARCH_HELPER_TEST_BASIC_AUTH_PASSWORD') ?: '',
      ]);
    }

    $settings->set('ssl', [
      'certificate' => getenv('ELASTICSEARCH_HELPER_TEST_SSL_CERTIFICATE') ?: '',
      'skip_verification' => getenv('ELASTICSEARCH_HELPER_TEST_SSL_SKIP_VERIFICATION') ?: FALSE,
    ]);

    // Save the config.
    $settings->save();
    // Clear static cache.
    $this->container->get('config.factory')->clearStaticCache();
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
   * Returns TRUE if SSL certificate needs to be verified.
   *
   * @return bool
   */
  protected function verifySslCertificate() {
    return (bool) $this->config('elasticsearch_helper.settings')->get('ssl.configuration.skip_verification') ?? FALSE;
  }

  /**
   * Returns Elasticsearch index manager instance.
   *
   * @return \Drupal\elasticsearch_helper\Plugin\ElasticsearchIndexManager
   */
  protected function getElasticsearchIndexManager() {
    return \Drupal::service('plugin.manager.elasticsearch_index.processor');
  }

  /**
   * Removes Elasticsearch indices used for testing purposes.
   */
  protected function removeIndices() {
    $index_manager = $this->getElasticsearchIndexManager();

    foreach ($index_manager->getDefinitions() as $plugin_id => $definition) {
      $index_manager->createInstance($plugin_id)->drop();
    }
  }

  /**
   * Creates Elasticsearch indices for testing purposes.
   */
  protected function createIndices() {
    $index_manager = $this->getElasticsearchIndexManager();

    foreach ($index_manager->getDefinitions() as $plugin_id => $definition) {
      $index_manager->createInstance($plugin_id)->setup();
    }
  }

  /**
   * Returns multilingual node index name (per language).
   *
   * @param $langcode
   *
   * @return string
   */
  protected function getMultilingualNodeIndexName($langcode) {
    $index_manager = $this->getElasticsearchIndexManager();
    $instance = $index_manager->createInstance('test_multilingual_node_index');

    return $instance->getIndexName(['langcode' => $langcode]);
  }

  /**
   * Returns simple node index name.
   *
   * @return string
   */
  protected function getSimpleNodeIndexName() {
    $index_manager = $this->getElasticsearchIndexManager();
    $instance = $index_manager->createInstance('test_simple_node_index');

    return $instance->getIndexName();
  }

}
