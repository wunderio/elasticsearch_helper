<?php

namespace Drupal\elasticsearch_helper;

/**
 * Defines Elasticsearch request result interface.
 */
interface ElasticsearchRequestResultInterface {

  /**
   * Returns Elasticsearch request wrapper instance.
   *
   * @return \Drupal\elasticsearch_helper\ElasticsearchRequestWrapperInterface
   */
  public function getRequestWrapper();

  /**
   * Returns Elasticsearch request result contents.
   *
   * @return mixed
   */
  public function getResultBody();

}
