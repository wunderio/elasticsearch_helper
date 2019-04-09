<?php

use Elasticsearch\ClientBuilder;
use Elasticsearch\Common\Exceptions\NoNodesAvailableException;

/**
 * Class ElasticsearchClientBuilder.
 *
 * @package Drupal\elasticsearch_helper
 */
class ElasticsearchClientBuilder {

  /**
   * Create an elasticsearch client.
   *
   * @return \Elasticsearch\Client
   */
  public function build() {
    $clientBuilder = ClientBuilder::create();
    $clientBuilder->setHosts($this->getHosts());

    drupal_alter('elasticsearch_helper_client_builder', $clientBuilder);

    return $clientBuilder->build();
  }

  /**
   * Get the hosts based on the site configuration.
   */
  protected function getHosts() {
    $host = implode(':', [
      variable_get('elasticsearch_helper_host', 'localhost'),
      variable_get('elasticsearch_helper_port', 9200),
    ]);

    if (variable_get('elasticsearch_helper_user')) {
      $credentials = implode(':', [
        variable_get('elasticsearch_helper_user', ''),
        variable_get('elasticsearch_helper_password', ''),
      ]);

      if (!empty($credentials)) {
        $host = implode('@', [$credentials, $host]);
      }
    }

    if ($scheme = variable_get('elasticsearch_helper_scheme', 'http')) {
      $host = implode('://', [
        $scheme,
        $host,
      ]);
    }

    return [$host];
  }

}
