<?php

namespace Drupal\elasticsearch_helper\Event;

/**
 * Class ElasticsearchOperations
 */
class ElasticsearchOperations {

  /**
   * Defines document index operation.
   */
  const DOCUMENT_INDEX = 'document.index';

  /**
   * Defines document retrieval operation.
   */
  const DOCUMENT_GET = 'document.get';

  /**
   * Defines document update operation.
   */
  const DOCUMENT_UPDATE = 'document.update';

  /**
   * Defines document upsert operation.
   */
  const DOCUMENT_UPSERT = 'document.upsert';

  /**
   * Defines document removal operation.
   */
  const DOCUMENT_DELETE = 'document.delete';

  /**
   * Defines document bulk-insert operation.
   */
  const DOCUMENT_BULK = 'document.bulk';

  /**
   * Defines index creation operation.
   */
  const INDEX_CREATE = 'index.create';

  /**
   * Defines index template creation operation.
   */
  const INDEX_TEMPLATE_CREATE = 'index_template.create';

  /**
   * Defines index retrieval operation.
   */
  const INDEX_GET = 'index.get';

  /**
   * Defines index exists operation.
   */
  const INDEX_EXISTS = 'index.exists';

  /**
   * Defines index removal operation.
   */
  const INDEX_DROP = 'index.drop';

  /**
   * Defines query search operation.
   */
  const QUERY_SEARCH = 'query.search';

  /**
   * Defines query multi-search operation.
   */
  const QUERY_MULTI_SEARCH = 'query.msearch';

}
