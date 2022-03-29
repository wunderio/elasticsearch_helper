<?php

namespace Drupal\elasticsearch_helper;

/**
 * Elasticsearch request result class.
 */
class ElasticsearchRequestResult implements ElasticsearchRequestResultInterface {

  /**
   * Elasticsearch request wrapper instance.
   *
   * @var \Drupal\elasticsearch_helper\ElasticsearchRequestWrapperInterface
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
   * @param \Drupal\elasticsearch_helper\ElasticsearchRequestWrapperInterface $request_wrapper
   * @param $result_body
   */
  public function __construct(ElasticsearchRequestWrapperInterface $request_wrapper, $result_body) {
    $this->requestWrapper = $request_wrapper;
    $this->resultBody = $result_body;
  }

  /**
   * {@inheritdoc}
   */
  public function getRequestWrapper() {
    return $this->requestWrapper;
  }

  /**
   * {@inheritdoc}
   */
  public function getResultBody() {
    return $this->resultBody;
  }

}
