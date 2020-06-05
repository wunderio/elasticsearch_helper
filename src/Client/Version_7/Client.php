<?php

namespace Drupal\elasticsearch_helper\Client\Version_7;

use Elasticsearch\Client as OriginalClient;
use Elasticsearch\Transport;
use Drupal\elasticsearch_helper\Client\Version_7\Namespaces\IndicesNamespace;

/**
 * Class Client
 *
 * Elasticsearch Helper 8.x-6.x has been proven to work with Elasticsearch-PHP
 * version 6.x of the box. Elasticsearch-PHP version 7.x introduces deprecation
 * messages in cases when types are provided. In order to support Elasticsearch
 * version 6 and 7, the following client libraries are used when client is
 * built:
 *   - If \Elasticsearch\Client::VERSION is >= 6 and < 7, \Elasticsearch\Client
 *     class is used.
 *   - If \Elasticsearch\Client::VERSION is >= 7, \Elasticsearch\Client class
 *     override is used (this class). In cases when Elasticsearch index plugins
 *     use legacy parameters originally used for Elasticsearch 6, they are
 *     modified by the override client class to make them compliant to
 *     Elasticsearch 7.
 */
class Client extends OriginalClient {

  /**
   * {@inheritdoc}
   */
  public function __construct(Transport $transport, callable $endpoint, array $registeredNamespaces) {
    parent::__construct($transport, $endpoint, $registeredNamespaces);

    // Override IndicesNamespace class.
    $this->indices = new IndicesNamespace($transport, $endpoint);
  }

  /**
   * Removes type from parameters.
   *
   * @param $params
   */
  protected function __removeType(&$params) {
    if (isset($params['type'])) {
      unset($params['type']);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function index(array $params = []) {
    $this->__removeType($params);

    return parent::index($params);
  }

  /**
   * {@inheritdoc}
   */
  public function get(array $params = []) {
    $this->__removeType($params);

    return parent::get($params);
  }

  /**
   * {@inheritdoc}
   */
  public function update(array $params = []) {
    $this->__removeType($params);

    return parent::update($params);
  }

  /**
   * {@inheritdoc}
   */
  public function delete(array $params = []) {
    $this->__removeType($params);

    return parent::delete($params);
  }

  /**
   * {@inheritdoc}
   */
  public function search(array $params = []) {
    $this->__removeType($params);

    return parent::search($params);
  }

  /**
   * {@inheritdoc}
   */
  public function msearch(array $params = []) {
    $this->__removeType($params);

    return parent::msearch($params);
  }

  /**
   * {@inheritdoc}
   */
  public function bulk(array $params = []) {
    $this->__removeType($params);

    return parent::bulk($params);
  }

}
