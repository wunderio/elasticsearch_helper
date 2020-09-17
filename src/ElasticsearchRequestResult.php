<?php

namespace Drupal\elasticsearch_helper;

/**
 * Class ElasticsearchRequestResult
 */
class ElasticsearchRequestResult {

  /**
   * Elasticsearch request wrapper instance.
   *
   * @var \Drupal\elasticsearch_helper\ElasticsearchRequestWrapper
   */
  protected $requestWrapper;

  /**
   * Elasticsearch request result body.
   *
   * @var mixed
   */
  protected $resultBody;

  /**
   * ElasticsearchRequestResult constructor.
   *
   * @param \Drupal\elasticsearch_helper\ElasticsearchRequestWrapper $request_wrapper
   * @param $result_body
   */
  public function __construct(ElasticsearchRequestWrapper $request_wrapper, $result_body) {
    $this->requestWrapper = $request_wrapper;
    $this->resultBody = $result_body;
  }

  /**
   * Returns Elasticsearch request wrapper instance.
   *
   * @return \Drupal\elasticsearch_helper\ElasticsearchRequestWrapper
   */
  public function getRequestWrapper() {
    return $this->requestWrapper;
  }

  /**
   * Returns Elasticsearch request result contents.
   *
   * @return mixed
   */
  public function getResultBody() {
    return $this->resultBody;
  }

}
