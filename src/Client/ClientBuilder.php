<?php

namespace Drupal\elasticsearch_helper\Client;

use Drupal\elasticsearch_helper\ElasticsearchClientBuilder;
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
    $class_name = ElasticsearchClientBuilder::getElasticsearchClientClassName();

    return new $class_name($transport, $endpoint, $registeredNamespaces);
  }

}
