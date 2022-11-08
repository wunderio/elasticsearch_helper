<?php

namespace Drupal\elasticsearch_helper\Plugin;

use Drupal\Component\Plugin\ConfigurableInterface;
use Drupal\Core\Plugin\PluginFormInterface;
use Elasticsearch\ClientBuilder;

/**
 * Defines an interface for Elasticsearch auth plugins.
 */
interface ElasticsearchAuthInterface extends ConfigurableInterface, PluginFormInterface {

  /**
   * Adds authentication information to the client builder.
   *
   * @param \Elasticsearch\ClientBuilder $client_builder
   *
   * @return void
   */
  public function authenticate(ClientBuilder $client_builder);

}
