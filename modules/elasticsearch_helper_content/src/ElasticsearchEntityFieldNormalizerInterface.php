<?php

namespace Drupal\elasticsearch_helper_content;

/**
 * Defines interface for Elasticsearch entity field normalizer plugins.
 *
 * This interface allows distinguish entity content normalizers from entity
 * field normalizers which allow per-field configuration.
 */
interface ElasticsearchEntityFieldNormalizerInterface extends ElasticsearchNormalizerInterface {

}
