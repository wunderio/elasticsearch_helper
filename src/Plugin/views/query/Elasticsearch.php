<?php

namespace Drupal\elasticsearch_helper_views\Plugin\views\query;

use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Cache\Cache;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\elasticsearch_helper_views\ElasticsearchQueryBuilderInterface;
use Drupal\elasticsearch_helper_views\ElasticsearchQueryBuilderManager;
use Drupal\views\Plugin\views\query\QueryPluginBase;
use Drupal\views\ResultRow;
use Drupal\views\ViewExecutable;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Elasticsearch\Client;

/**
 * Views query plugin for an Elasticsearch query.
 *
 * @ingroup views_query_plugins
 *
 * @ViewsQuery(
 *   id = "elasticsearch_query",
 *   title = @Translation("Elasticsearch Query"),
 *   help = @Translation("Query will be generated and run using the Elasticsearch API.")
 * )
 */
class Elasticsearch extends QueryPluginBase {

  /** @var \Elasticsearch\Client $elasticsearchClient */
  protected $elasticsearchClient;

  /** @var  EntityTypeManagerInterface $entityTypeManager */
  protected $entityTypeManager;

  /** @var \Drupal\elasticsearch_helper_views\ElasticsearchQueryBuilderManager $elasticsearchQueryBuilderManager */
  protected $elasticsearchQueryBuilderManager;

  /** @var \Drupal\elasticsearch_helper_views\ElasticsearchQueryBuilderInterface $queryBuilder */
  protected $queryBuilder;

  /**
   * Elasticsearch constructor.
   *
   * @param array $configuration
   * @param string $plugin_id
   * @param mixed $plugin_definition
   * @param \Elasticsearch\Client $elasticsearch_client
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   * @param \Drupal\elasticsearch_helper_views\ElasticsearchQueryBuilderManager $query_builder_manager
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, Client $elasticsearch_client, EntityTypeManagerInterface $entity_type_manager, ElasticsearchQueryBuilderManager $query_builder_manager) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->elasticsearchClient = $elasticsearch_client;
    $this->entityTypeManager = $entity_type_manager;
    $this->elasticsearchQueryBuilderManager = $query_builder_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('elasticsearch_helper.elasticsearch_client'),
      $container->get('entity_type.manager'),
      $container->get('elasticsearch_query_builder.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  protected function setOptionDefaults(array &$storage, array $options) {
    parent::setOptionDefaults($storage, $options);
    $storage['query_builder'] = '';
    $storage['entity_relationship'] = [
      'entity_type_key' => '',
      'entity_id_key' => '',
    ];
  }

  /**
   * Provides query options form.
   *
   * @param $form
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   */
  public function buildOptionsForm(&$form, FormStateInterface $form_state) {
    parent::buildOptionsForm($form, $form_state);

    $query_builder_options = [];
    foreach ($this->elasticsearchQueryBuilderManager->getDefinitions() as $query_builder_plugin) {
      $query_builder_options[$query_builder_plugin['id']] = sprintf('%s (%s)', $query_builder_plugin['label'], $query_builder_plugin['id']);
    }

    $form['query_builder'] = array(
      '#type' => 'select',
      '#title' => $this->t('Elasticsearch query builder'),
      '#empty_value' => '',
      '#options' => $query_builder_options,
      '#default_value' => $this->options['query_builder'],
      '#required' => FALSE,
    );

    $form['entity_relationship'] = array(
      '#type' => 'details',
      '#title' => $this->t('Entity relationship'),
      '#description' => $this->t('Define default entity relationship.'),
      '#open' => TRUE,
    );

    $form['entity_relationship']['entity_type_key'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Entity type field'),
      '#description' => $this->t('A field in Elasticsearch results which contains entity type name. To set a fixed value, prefix the string with @ (e.g., @node).'),
      '#default_value' => $this->options['entity_relationship']['entity_type_key'],
    );

    $form['entity_relationship']['entity_id_key'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Entity ID field'),
      '#description' => $this->t('A field in Elasticsearch results which contains entity ID value.'),
      '#default_value' => $this->options['entity_relationship']['entity_id_key'],
      '#group' => 'entity_type_key',
    );
  }

  /**
   * Builds the necessary info to execute the query.
   */
  public function build(ViewExecutable $view) {
    // Store the view in the object to be able to use it later.
    $this->view = $view;

    // Initiate pager.
    $view->initPager();

    // Let the pager modify the query to add limits.
    $view->pager->query();

    $view->build_info['query'] = $this->query();
  }

  /**
   * Returns an empty array as there's no physical table in Elasticsearch.
   *
   * @param $table
   * @param null $relationship
   *
   * @return string
   */
  public function ensureTable($table, $relationship = NULL) {
    return '';
  }

  /**
   * Returns the field as is as there's no need to limit fields in result set.
   *
   * @param $table
   * @param $field
   * @param string $alias
   * @param array $params
   *
   * @return mixed
   */
  public function addField($table, $field, $alias = '', $params = array()) {
    return $field;
  }

  /**
   * Placeholder method.
   *
   * @param $group
   * @param $field
   * @param null $value
   * @param null $operator
   */
  public function addWhere($group, $field, $value = NULL, $operator = NULL) {
  }

  /**
   * Placeholder method.
   *
   * @param $group
   * @param $snippet
   * @param array $args
   */
  public function addWhereExpression($group, $snippet, $args = array()) {
  }

  /**
   * Placeholder method.
   *
   * @param $table
   * @param null $field
   * @param string $order
   * @param string $alias
   * @param array $params
   */
  public function addOrderBy($table, $field = NULL, $order = 'ASC', $alias = '', $params = array()) {
  }

  /**
   * Placeholder method.
   *
   * @param $clause
   */
  public function addGroupBy($clause) {
  }

  /**
   * Placeholder method.
   */
  public function addRelationship() {
  }

  /**
   * Returns instance of a query builder plugin.
   *
   * @return \Drupal\elasticsearch_helper_views\ElasticsearchQueryBuilderInterface|null
   */
  public function getQueryBuilder() {
    if ($this->options['query_builder'] && !isset($this->queryBuilder)) {
      try {
        $this->queryBuilder = $this->elasticsearchQueryBuilderManager->createInstance($this->options['query_builder']);
      } catch (\Exception $e) {
        watchdog_exception('elasticsearch_helper_views', $e);
      }
    }

    return $this->queryBuilder;
  }

  /**
   * {@inheritdoc}
   */
  public function query($get_count = FALSE) {
    /** @var ElasticsearchQueryBuilderInterface $query_builder */
    $query_builder = $this->getQueryBuilder();
    $query = $query_builder->buildQuery($this->view);

    // Apply limit and offset to the query.
    $limits = [
      'size' => $this->getLimit(),
      'from' => $this->offset,
    ];

    return array_merge($limits, $query);
  }

  /**
   * {@inheritdoc}
   */
  public function validate() {
    $errors = [];

    // Validate query builder settings (on created views only).
    if (!$this->view->storage->isNew() && empty($this->options['query_builder'])) {
      $errors[] = $this->t('Query builder plugin needs to be defined for this view to work. Configure query builder in the query settings.');
    }

    return $errors;
  }

  /**
   * Returns result row from a search hit.
   *
   * @param array $hit
   * @param $index
   *
   * @return \Drupal\views\ResultRow
   */
  protected function createResultRowFromHit(array $hit, $index) {
    return new ResultRow($hit);
  }

  /**
   * Indexes the result set.
   *
   * @param \Drupal\views\ResultRow[] $result
   */
  protected function indexResult(array &$result) {
    array_walk($result, function(ResultRow $row, $index) {
      $row->index = $index;
    });
  }

  /**
   * {@inheritdoc}
   */
  public function execute(ViewExecutable $view) {
    $query = $view->build_info['query'];
    $data = [];
    $result = [];

    try {
      if ($data = $this->elasticsearchClient->search($query)) {
        $index = 0;
        foreach ($data['hits']['hits'] as $hit) {
          $result[] = $this->createResultRowFromHit($hit, $index);
          $index++;
        }
      }
    } catch (\Exception $e) {
      watchdog_exception('elasticsearch_helper_views', $e);
    }

    $this->indexResult($result);
    $view->result = $result;

    $view->pager->postExecute($view->result);
    $view->pager->total_items = isset($data['hits']['total']) ? $data['hits']['total'] : 0;
    $view->pager->updatePageInfo();
    $view->total_rows = $view->pager->getTotalItems();

    // Load all entities contained in the results.
    $this->loadEntities($result);
  }

  /**
   * Returns nested value from the Elasticsearch result.
   *
   * Examples:
   *
   * - "id" will return value of element "id".
   * - "_source.id" will return a value of _source][id].
   * - "@node" will return "node" (value will not be determined).
   *
   * @param $key
   * @param array|object $data
   * @param $separator
   * @param $default
   *
   * @return mixed|null
   */
  protected function getNestedValue($key, $data, $separator = '.', $default = NULL) {
    // If $key starts with a @, it means that the key should be returned as a
    // string and there's no need to look for a value.
    if (isset($key[0]) && $key[0] == '@') {
      return substr($key, 1);
    }

    if (!is_array($data) && !is_object($data)) {
      return NULL;
    }

    // Cast $data into an array so that $data can be processed with NestedArray.
    if (is_object($data)) {
      $data = (array) $data;
    }

    $parts = explode($separator, $key);

    if (count($parts) == 1) {
      return isset($data[$key]) ? $data[$key] : $default;
    }
    else {
      $value = NestedArray::getValue($data, $parts, $key_exists);
      return $key_exists ? $value : $default;
    }
  }

  /**
   * Returns a list of entity relationship information, keyed by relationship
   * keys.
   *
   * Also only valid relationship information is returned (i.e., with defined
   * entity type and entity ID keys).
   *
   * @return array
   *
   * @see hook_views_data_alter()
   */
  public function getEntityRelationships() {
    $result = [];

    foreach ($this->displayHandler->getHandlers('relationship') as $handler_id => $handler) {
      if (isset($handler->options['entity_type_key'], $handler->options['entity_id_key'])) {
        $result[$handler_id] = [
          'entity_type_key' => $handler->options['entity_type_key'],
          'entity_id_key' => $handler->options['entity_id_key'],
        ];
      }
    }

    return ['none' => $this->options['entity_relationship']] + $result;
  }

  /**
   * Loads all entities contained in the passed-in $results.
   *
   * Entities defined by a "entity_relationship" relationship are stored in
   * $result->_relationship_entities[$relationship_id];
   *
   * @param \Drupal\views\ResultRow[] $results
   *   The result of the SQL query.
   */
  public function loadEntities(&$results) {
    $entity_relationships = $this->getEntityRelationships();

    // No entity tables found, nothing else to do here.
    if (empty($entity_relationships)) {
      return;
    }

    $entity_types = array_keys($this->entityTypeManager->getDefinitions());
    $entity_ids_by_type = [];

    foreach ($entity_relationships as $relationship_id => $info) {
      foreach ($results as $index => $result) {
        // Get entity type value from result.
        $entity_type = $this->getNestedValue($info['entity_type_key'], $result);

        if (isset($entity_type) && in_array($entity_type, $entity_types)) {
          // Get entity ID value from result.
          $entity_id = $this->getNestedValue($info['entity_id_key'], $result);

          if (isset($entity_id)) {
            $entity_ids_by_type[$entity_type][$index][$relationship_id] = $entity_id;
          }
        }
      }
    }

    // Load all entities and assign them to the correct result row.
    foreach ($entity_ids_by_type as $entity_type => $ids) {
      $entity_storage = $this->entityTypeManager->getStorage($entity_type);
      $flat_ids = iterator_to_array(new \RecursiveIteratorIterator(new \RecursiveArrayIterator($ids)), FALSE);

      $entities = $entity_storage->loadMultiple(array_unique($flat_ids));
      $results = $this->assignEntitiesToResult($ids, $entities, $results);
    }
  }

  /**
   * Sets entities onto the view result row objects.
   *
   * This method takes into account the relationship in which the entity was
   * needed in the first place.
   *
   * @param mixed[] $ids
   *   An array of identifiers (entity ID / revision ID).
   * @param \Drupal\Core\Entity\EntityInterface[] $entities
   *   An array of entities keyed by their identified (entity ID / revision ID).
   * @param \Drupal\views\ResultRow[] $results
   *   The entire views result.
   *
   * @return \Drupal\views\ResultRow[]
   *   The changed views results.
   */
  protected function assignEntitiesToResult($ids, array $entities, array $results) {
    foreach ($ids as $index => $relationships) {
      foreach ($relationships as $relationship_id => $id) {
        $entity = NULL;

        if (isset($entities[$id])) {
          $entity = $entities[$id];
        }

        if ($entity) {
          if ($relationship_id == 'none') {
            $results[$index]->_entity = $entity;
          }
          else {
            $results[$index]->_relationship_entities[$relationship_id] = $entity;
          }
        }
      }
    }

    return $results;
  }

  /**
   * Gets all the involved entities of the view.
   *
   * @return \Drupal\Core\Entity\EntityInterface[]
   */
  protected function getAllEntities() {
    $entities = [];
    foreach ($this->view->result as $row) {
      if ($row->_entity) {
        $entities[] = $row->_entity;
      }
      foreach ($row->_relationship_entities as $entity) {
        $entities[] = $entity;
      }
    }

    return $entities;
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheTags() {
    $tags = [];

    foreach ($this->getAllEntities() as $entity) {
      $tags = Cache::mergeTags($entity->getCacheTags(), $tags);
    }

    return $tags;
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheMaxAge() {
    $max_age = parent::getCacheMaxAge();
    foreach ($this->getAllEntities() as $entity) {
      $max_age = Cache::mergeMaxAges($max_age, $entity->getCacheMaxAge());
    }

    return $max_age;
  }

}
