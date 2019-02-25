<?php

namespace Drupal\elasticsearch_helper_content;

/**
 * Defines interface for Elasticsearch entity content normalizer plugins.
 *
 * This interface allows distinguish entity content normalizers from entity
 * field normalizers which allow per-field configuration.
 */
interface ElasticsearchEntityContentNormalizerInterface extends ElasticsearchNormalizerInterface {

}
