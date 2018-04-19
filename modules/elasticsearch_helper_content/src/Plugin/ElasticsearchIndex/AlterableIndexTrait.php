<?php

namespace Drupal\elasticsearch_helper_content\Plugin\ElasticsearchIndex;

/**
 * A trait to make the index method of an index alterable.
 */
trait AlterableIndexTrait {

  public function index($source) {
    $source_original = $source;

    // Let custom project specific alter hook do bundle filtering etc.
    \Drupal::moduleHandler()->alter('elasticsearch_helper_content_source', $source);

    if ($source) {
      parent::index($source);
    }
    else {
      // If source was set to FALSE by an alter hook implementation, delete it.
      $this->delete($source_original);
    }
  }

}
