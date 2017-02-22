<?php

namespace Drupal\elasticsearch_helper_views\Plugin\views\query;

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
    $storage['elasticserach_query_builder'] = '';
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

    $form['elasticserach_query_builder'] = array(
      '#type' => 'select',
      '#title' => $this->t('Elasticsearch query builder'),
      '#empty_value' => '',
      '#options' => $query_builder_options,
      '#default_value' => $this->options['elasticserach_query_builder'],
      '#required' => FALSE,
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
   * {@inheritdoc}
   */
  public function query($get_count = FALSE) {
    /** @var ElasticsearchQueryBuilderInterface $query_builder */
    $query_builder = $this->elasticsearchQueryBuilderManager->createInstance($this->options['elasticserach_query_builder']);
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
  public function execute(ViewExecutable $view) {
    $query = $view->build_info['query'];
    $result = [];

    if ($data = $this->elasticsearchClient->search($query)) {
      $index = 0;
      foreach ($data['hits']['hits'] as $hit) {
        $row['id'] = $hit['_source']['id'];
        $row['entity_type'] = $hit['_type'];
        $row['index'] = $index++;
        $result[] = new ResultRow($row);
      }
    }

    $view->result = $result;

    $view->pager->postExecute($view->result);
    $view->pager->total_items = $data['hits']['total'];
    $view->pager->updatePageInfo();
    $view->total_rows = $view->pager->getTotalItems();

    // Load all entities contained in the results.
    $this->loadEntities($result);
  }

  /**
   * Returns an empty array as there's no physical table for Elasticsearch.
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
   * Loads all entities contained in the passed-in $results.
   *.
   * If the entity belongs to the base table, then it gets stored in
   * $result->_entity. Otherwise, it gets stored in
   * $result->_relationship_entities[$relationship_id];
   *
   * @param \Drupal\views\ResultRow[] $results
   *   The result of the SQL query.
   */
  public function loadEntities(&$results) {
    $entity_types = array_keys($this->entityTypeManager->getDefinitions());
    $entity_ids_by_type = [];

    foreach ($results as $index => $result) {
      // Store entity ID if found.
      if (!empty($result->entity_type) && in_array($result->entity_type, $entity_types)) {
        $entity_ids_by_type[$result->entity_type][$index] = $result->id;
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
    foreach ($ids as $index => $id) {
      if (isset($entities[$id])) {
        $entity = $entities[$id];
      }
      else {
        $entity = NULL;
      }

      $results[$index]->_entity = $entity;
    }

    return $results;
  }

}
