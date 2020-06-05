<?php

namespace Drupal\elasticsearch_helper\Client;

use Elasticsearch\Client;
use Elasticsearch\ClientBuilder as OriginalClientBuilder;
use Elasticsearch\Transport;

/**
 * Class ClientBuilder
 *
 * Overrides the original \Elasticsearch\ClientBuilder and returns
 * overridden \Elasticsearch\Client class.
 */
class ClientBuilder extends OriginalClientBuilder {

  /**
   * {@inheritdoc}
   */
  protected function instantiate(Transport $transport, callable $endpoint, array $registeredNamespaces): Client {
    $class_name = $this->getElasticsearchClientClassName(Client::VERSION);

    return new $class_name($transport, $endpoint, $registeredNamespaces);
  }

  /**
   * Returns Elasticsearch client class name based on provided version.
   *
   * @param $version
   *
   * @return string|null
   */
  protected function getElasticsearchClientClassName($version) {
    if (version_compare($version, '7.0') >= 0) {
      return '\Drupal\elasticsearch_helper\Client\Version_7\Client';
    }
    elseif (version_compare($version, '5.0') >= 0) {
      return '\Elasticsearch\Client';
    }

    return NULL;
  }

}
