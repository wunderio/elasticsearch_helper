<?php

namespace Drupal\elasticsearch_helper\Plugin\Action;

use Drupal\Core\Action\ActionBase;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Session\AccountInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\elasticsearch_helper\Plugin\ElasticsearchIndexManager;

/**
 * Elasticsearch entity re-index action.
 *
 * @Action(
 *   id = "elasticsearch_helper_reindex",
 *   label = @Translation("Re-index entity"),
 * )
 */
class ReindexAction extends ActionBase implements ContainerFactoryPluginInterface {

  /**
   * @var ElasticsearchIndexManager
   */
  protected $elasticsearchPluginManager;

  /**
   * Constructs an ReindexAction object.
   *
   * @param \Drupal\elasticsearch_helper\Plugin\ElasticsearchIndexManager $manager
   *   The Elasticsearch index plugin manager.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, ElasticsearchIndexManager $manager) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);

    $this->elasticsearchPluginManager = $manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('plugin.manager.elasticsearch_index.processor')
    );
  }


  /**
   * {@inheritdoc}
   */
  public function execute($entity = NULL) {
    $this->elasticsearchPluginManager->indexEntity($entity);
  }

  /**
   * {@inheritdoc}
   */
  public function access($object, AccountInterface $account = NULL, $return_as_object = FALSE) {
    /** @var \Drupal\Core\Access\AccessResultInterface $result */
    $result = $object->access('update', $account, TRUE);

    return $return_as_object ? $result : $result->isAllowed();
  }

}
