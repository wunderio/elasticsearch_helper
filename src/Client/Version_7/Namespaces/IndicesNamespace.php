<?php

namespace Drupal\elasticsearch_helper\Client\Version_7\Namespaces;

use Elasticsearch\Namespaces\IndicesNamespace as OriginalIndicesNamespace;

/**
 * Class IndicesNamespace
 */
class IndicesNamespace extends OriginalIndicesNamespace {

  /**
   * {@inheritdoc}
   */
  public function create(array $params = []) {
    // If "settings" and "mapping" are not provided, assume the body contains
    // the settings.
    if (!empty($params['body'])) {
      if (!isset($params['body']['settings']) && !isset($params['body']['mapping'])) {
        $params['body'] = [
          'settings' => $params['body'],
        ];
      }
    }

    return parent::create($params);
  }

  /**
   * {@inheritdoc}
   */
  public function putMapping(array $params = []) {
    if (isset($params['type'])) {
      unset($params['type']);
    }

    return parent::putMapping($params);
  }

}
