<?php

namespace Drupal\elasticsearch_helper_views\Ajax;

use Drupal\Core\Ajax\CommandInterface;

/**
 * Prints Elasticsearch query to console for debugging.
 *
 * @ingroup ajax
 */
class ElasticsearchDebugCommand implements CommandInterface {

  /** @var string $text */
  protected $text;

  /**
   * ElasticsearchPrintToConsoleCommand constructor.
   *
   * @param $text
   */
  public function __construct($text) {
    $this->text = $text;
  }

  /**
   * Implements \Drupal\Core\Ajax\CommandInterface:render().
   */
  public function render() {
    return [
      'command' => 'elasticsearch_query_debug',
      'text' => $this->text,
    ];
  }

}
