<?php

namespace Drupal\elasticsearch_helper_index_management\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Link;
use Drupal\elasticsearch_helper\Plugin\ElasticsearchIndexManager;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class ListController.
 */
class ListController extends ControllerBase {

  /**
   * Drupal\elasticsearch_helper\Plugin\ElasticsearchIndexManager definition.
   *
   * @var \Drupal\elasticsearch_helper\Plugin\ElasticsearchIndexManager
   */
  protected $elasticsearchHelperPluginManager;

  /**
   * Constructs a new ListController object.
   */
  public function __construct(ElasticsearchIndexManager $elasticsearch_plugin_manager) {
    $this->elasticsearchHelperPluginManager = $elasticsearch_plugin_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('plugin.manager.elasticsearch_index.processor')
    );
  }

  /**
   * List index plugins.
   *
   * @return array
   *   List markup.
   */
  public function display() {
    // Define headers.
    $header = [
      $this->t('Name'),
      $this->t('Index ID'),
      $this->t('Entity Type'),
      $this->t('Action'),
    ];

    // Build rows.
    $rows = [];

    foreach ($this->elasticsearchHelperPluginManager->getDefinitions() as $plugin) {
      if (isset($plugin['entityType'])) {
        $action = Link::createFromRoute(
          $this->t('Manage'),
          'elasticsearch_helper_index_management.reindex_controller_status',
          ['index_id' => $plugin['id']],
          ['attributes' => ['class' => 'button']]
        );

        $rows[] = [
          $plugin['label'],
          $plugin['id'],
          $plugin['entityType'],
          $action,
        ];
      }
    }

    return [
      '#type' => 'table',
      '#header' => $header,
      '#rows' => $rows,
    ];
  }

}
