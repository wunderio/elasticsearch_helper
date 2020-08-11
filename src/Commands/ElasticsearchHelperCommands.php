<?php
namespace Drupal\elasticsearch_helper\Commands;

use Consolidation\OutputFormatters\StructuredData\RowsOfFields;
use Drupal\elasticsearch_helper\Plugin\ElasticsearchIndexManager;
use Drush\Commands\DrushCommands;
use Symfony\Component\Console\Helper\Table;

/**
 *
 */
class ElasticsearchHelperCommands extends DrushCommands {

  /**
   * @var ElasticsearchIndexManager
   */
  protected $elasticsearchPluginManager;

  /**
   * ElasticsearchHelperCommands constructor.
   *
   * @param \Drupal\elasticsearch_helper\Plugin\ElasticsearchIndexManager $manager
   *   The Elasticsearch index plugin manager.
   */
  public function __construct(ElasticsearchIndexManager $manager) {
    $this->elasticsearchPluginManager = $manager;
  }

  /**
   * Lists Elasticsearch index plugins.
   *
   * @command elasticsearch:helper:list
   * @table-style default
   * @field-labels
   *   id: id
   *   label: Name
   * @default-fields id,label
   * @aliases eshl,elasticsearch-helper-list
   *
   * @return \Consolidation\OutputFormatters\StructuredData\RowsOfFields
   */
  public function helperList() {
    $rows = [];
    foreach ($this->elasticsearchPluginManager->getDefinitions() as $plugin) {
      $rows[] = [
        'id' => $plugin['id'],
        'label' => $plugin['label'],
      ];
    }

    return new RowsOfFields($rows);
  }

  /**
   * Creates Elasticsearch indices.
   *
   * @param string|null $indices
   *   Comma separated list of indices to be set up
   *
   * @command elasticsearch:helper:setup
   * @aliases eshs,elasticsearch-helper-setup
   *
   * @throws \Drupal\Component\Plugin\Exception\PluginException
   */
  public function helperSetup($indices = NULL) {
    // Indices can be specified with a comma-separate value.
    if ($indices && is_string($indices)) {
      $indices = explode(',', $indices);
    }
    foreach ($this->elasticsearchPluginManager->getDefinitions() as $plugin) {
      if (!$indices || in_array($plugin['id'], $indices)) {
        $this->elasticsearchPluginManager->createInstance($plugin['id'])->setup();
      }
    }
  }

  /**
   * Drops Elasticsearch indices.
   *
   * @param string|null $indices
   *   Comma separated list of indices to be deleted
   *
   * @command elasticsearch:helper:drop
   * @aliases eshd,elasticsearch-helper-drop
   *
   * @throws \Drupal\Component\Plugin\Exception\PluginException
   */
  public function helperDrop($indices = NULL) {
    // Indices can be specified with a comma-separate value.
    if ($indices && is_string($indices)) {
      $indices = explode(',', $indices);
    }

    foreach ($this->elasticsearchPluginManager->getDefinitions() as $plugin) {
      if (!$indices || in_array($plugin['id'], $indices)) {

        $pluginInstance = $this->elasticsearchPluginManager->createInstance($plugin['id']);

        $rows = [];
        foreach ($pluginInstance->getExistingIndices() as $index) {
          $rows[] = [$index];
        }

        if (count($rows)) {
          $this->output()->writeln('The following indices exist in elasticsearch:');
          $table = new Table($this->output());
          $table->addRows($rows);
          $table->render();
          if ($this->io()->confirm(dt('Are you sure you want to delete them?'))) {
            $pluginInstance->drop();
          }
        }
        else {
          $this->output()->writeln('There are no indices to be deleted.');
        }
      }
    }
  }

  /**
   * Re-indexes entities associated with an Elasticsearch index plugin.
   *
   * @param string|null $indices
   *   Comma separated list of indices for which entities should be re-indexed.
   *
   * @command elasticsearch:helper:reindex
   * @aliases eshr,elasticsearch-helper-reindex
   *
   * @throws \Drupal\Component\Plugin\Exception\PluginException
   * @throws \Exception
   */
  public function helperReindex($indices = NULL) {
    // Indices can be specified with a comma-separate value.
    if ($indices && is_string($indices)) {
      $indices = explode(',', $indices);
    }

    $context = ['caller' => 'drush'];
    $this->elasticsearchPluginManager->reindex($indices, $context);
  }

}
