<?php

namespace Drupal\elasticsearch_helper_instant;

use Drupal\Core\Url;
use Drupal\Core\Entity\EntityTypeManager;
use Drupal\Core\Render\Renderer;
use Elasticsearch\Client;
use Drupal\Core\Language\LanguageManager;
use Drupal\Core\Logger\LoggerChannel;

/**
 * Service for a sane default search based on elasticssearch_helper.
 */
class ElasticsearchInstantSearchService {

  /**
   * The elasticsearch_helper.elasticsearch_client service.
   *
   * @var \Elasticsearch\Client
   */
  protected $client;

  /**
   * The logger.channel.elasticsearch_helper_instant service.
   *
   * @var \Drupal\Core\Logger\LoggerChannel
   */
  protected $logger;

  /**
   * The language_manager service.
   *
   * @var \Drupal\Core\Language\LanguageManager
   */
  protected $languageManager;

  /**
   * The entity_type.manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManager
   */
  protected $entityTypeManager;

  /**
   * The renderer service.
   *
   * @var \Drupal\Core\Render\Renderer
   */
  protected $renderer;

  /**
   * SearchProductFilter constructor.
   *
   * @param \Elasticsearch\Client $client
   *   Elasticsearch Client.
   * @param \Drupal\Core\Logger\LoggerChannel $logger
   *   Logger.
   * @param \Drupal\Core\Language\LanguageManager $language_manager
   *   Language manager.
   * @param \Drupal\Core\Entity\EntityTypeManager $entity_type_manager
   *   The entity_type.manager service injected by the container.
   * @param \Drupal\Core\Render\Renderer $renderer
   *   The renderer service injected by the container.
   */
  public function __construct(
    Client $client,
    LoggerChannel $logger,
    LanguageManager $language_manager,
    EntityTypeManager $entity_type_manager,
    Renderer $renderer
  ) {
    $this->client = $client;
    $this->logger = $logger;
    $this->languageManager = $language_manager;
    $this->entityTypeManager = $entity_type_manager;
    $this->renderer = $renderer;
  }

  /**
   * Queries ES for given fulltext $searchphrase.
   *
   * @param string $searchphrase
   *   The search phrase to query ES for.
   *
   * @return array
   *   Result json represented as array.
   */
  public function query($searchphrase) {
    if (empty(trim($searchphrase))) {
      return [];
    }

    $langcode = $this->languageManager->getCurrentLanguage()->getId();

    // First try phrase as is.
    $query = $this->buildQuery($searchphrase, $langcode);
    $result = $this->client->search($query);

    // If no results turned up, try again with wildcards prepared.
    if ($result['hits']['total'] < 1) {
      $searchphrase = $this->preparePhrase($searchphrase);
      $query = $this->buildQuery($searchphrase, $langcode);
      $result = $this->client->search($query);
    }

    $result['searchphrase'] = $searchphrase;
    $result['query'] = $query;

    foreach($result['hits']['hits'] ?? [] as $index => $hit) {
       $this->modifySearchResult($result['hits']['hits'][$index]['_source']);
    }

    return $result;
  }

  /**
   * Modify a single search result hit.
   *
   * @param array $hit
   *   The single search result hit as an array.
   */
  public function modifySearchResult(array &$hit) {
    // Set the internal url to a freshly generated full valid uri.
    // (This includes any current language prefix).
    // Todo: Discuss if this can be done better at index time instead.
    $hit['url_internal'] = Url::fromUri('entity:' . $hit['entity'] . '/' . $hit['id'])->toString();
  }

  /**
   * Builds the ES query.
   *
   * @param string $searchphrase
   *   The search phrase to create a query for.
   * @param string $langcode
   *   The language code to retrieve search results in.
   * @return array
   *   The query as an array.
   */
  public function buildQuery($searchphrase, $langcode) {
    $query = [
      'body' => [
        'query' => [
          'bool' => [
            'must' => [
              // We chose query_string over multi_match.
              // This allows for more control and mightier search.
              'query_string' => [
                'query' => $searchphrase,
                'fields' => [
                  // Boost matches in the label (=title/name).
                  'label^5',
                  'content',
                ],
              ],
            ],
            'filter' => $this->bool('must',
              $this->optionalTermFilter('status', 1),
              $this->optionalTermFilter('langcode', $langcode)
            ),
          ],
        ],
        'highlight' => [
          'fields' => [
            'content' => ['type' => 'unified'],
          ],
        ],
      ],
    ];

    return $query;
  }

  /**
   * Prepare $phrase for search.
   *
   * * Prepares each word.
   * * Ammend a wildcard.
   *
   * @param string $phrase
   *   A text phrase to prepare for use in ES query.
   * @param array $options
   *   Options for the method, currently:
   *   'wildcard' => _TRUE_|FALSE Whether to ammend wildcard at the end.
   */
  public function preparePhrase($phrase, array $options = []) {
    $options = $options + [
      'wildcard' => TRUE,
    ];

    $phrase = trim($phrase);

    // Quoted phrases remain unchanged since they imply an exact search.
    if ($phrase[0] == '"') {
      return $phrase;
    }

    // Split phrase into words by whitespaces.
    $words = preg_split('/\s+/', $phrase, -1, PREG_SPLIT_NO_EMPTY);

    // Prepare each word.
    foreach ($words as $index => $word) {
      $words[$index] = $this->prepareWord($word, $phrase, $index, $options);
    }

    // Join back words.
    $result = implode(' ', $words);

    // Optionally amend wildcard (if not there / if no quoted phrase).
    if ($options['wildcard']) {
      if (!in_array(substr($result, -1), ['*', '"'])) {
        $result .= '*';
      }
    }

    return $result;
  }

  /**
   * Prepare a single word from a search phrase.
   *
   * @param string $word
   *   A single word from a search phrase to prepare.
   * @param string $phrase
   *   The whole search phrase for reference.
   * @param int $index
   *   The index of the $word in the sequence of words in the $phrase.
   * @param array $options
   *   Options for the method, currently:
   *   'wildcard' => _TRUE_|FALSE Whether to ammend wildcard at the end.
   *
   * @return string
   *   The prepared word.
   */
  public function prepareWord($word, $phrase = '', $index = 0, array $options = []) {
    if (is_numeric($word)) {
      // In case of a number (we assume: _telephone_ number):
      // Add second word with leading zero removed and wildcards.
      $word2 = ($word[0] === '0') ? substr($word, 1) : $word;
      if ($options['wildcard'] ?? FALSE) {
        $word2 = "*$word2*";
      }
      $word = "$word $word2";
    }

    // Quote words containing a '-' sign, to avoid ES interpret it as "not".
    if (strpos($word, '-')) {
      if (substr($word, -1) != '"') {
        $word = '"' . $word . '"';
      }
    }
    return $word;
  }

  /**
   * Construct a bool query with given operator and any number of arguments.
   *
   * A helper function for easier building queries in the query method.
   *
   * @param string $op
   *   The operator to use with the bool query, see https://goo.gl/gDiSPZ.
   * @param string $arguments
   *   One or more additional arguments to use with the bool query.
   *
   * @return array
   *   The constructed bool query array.
   */
  public function bool($op, $arguments) {
    $json = [
      'bool' => [
        $op => array_slice(func_get_args(), 1),
      ],
    ];
    return $json;
  }

  /**
   * Construct an _optional_ term filter query (without "filter").
   *
   * A helper function for easier building queries in the query method.
   * The $fieldname can either not exist or must be $value.
   *
   * @param string $fieldname
   *   The ES field name to filter on.
   * @param string $value
   *   The value to filter for.
   *
   * @return array
   *   The part of the query declaring the filter.
   */
  public function optionalTermFilter($fieldname, $value) {
    return $this->bool('should',
      $this->bool('must',
        ['exists' => ['field' => $fieldname]],
        ['term' => [$fieldname => $value]]
      ),
      $this->bool('must_not',
        ['exists' => ['field' => $fieldname]]
      )
    );
  }

  /**
   * Renders the result into an output string.
   *
   * @param array $result
   *   Result array representing the json result of elasticssearch.
   * @param array $options
   *   Any of these options:
   *    'render' => _'stored'_|'live'
   *    'format' => _'html'_|'json'|'raw'.
   *
   * @return string|array
   *   Search output either as string ('format'=>'html') or as jsonifiable
   *   array of hit _source entries.
   */
  public function render(array $result, array $options = []) {
    $options = $options + [
      'rendermode' => 'stored',
      'format' => 'json',
      'debug' => FALSE,
    ];

    // Raw output is returned right away.
    if ($options['format'] == 'raw') {
      return $result;
    }

    // Loop through hits and build output array.
    $output = [];
    foreach ($result['hits']['hits'] ?? [] as $hit) {
      $hit['_source']['_score'] = $hit['_score'];
      $render = &$hit['_source']['rendered_search_result'];

      // If specified, replace stored render with fresh live render.
      if ($options['rendermode'] == 'live') {
        $entity = $this->entityTypeManager->getStorage($hit['_source']['entity'])->load($hit['_source']['id']);
        $render_array = $this->entityTypeManager->getViewBuilder($hit['_source']['entity'])->view($entity, 'search_result');
        $render = $this->renderer->renderPlain($render_array);
      }

      // Replace highlight placeholder with highlight:
      $highlight = implode(' … ', $hit['highlight']['content']);
      $highlight = $this->getExcerptMarkup($highlight);
      $placeholder = $this->getExcerptPlaceholder();
      $render = str_replace($placeholder, $highlight, $render);

      // Optionally amend some quick debug output.
      if ($options['debug']) {
        $render = $render . "\nsearchphrase:" . $result['searchphrase'] . ' timestamp:' . time();
      }

      $output[] = ($options['format'] == 'html') ? $render : $hit['_source'];
    }

    // If html specified, concatenated output string.
    if (($options['format'] == 'html')) {
      $output = implode("\n", $output);
    }

    return $output;
  }

  /**
   * Format an excerpt string.
   *
   * @param string $excerpt
   *   The excerpt to format.
   *
   * @return string
   *   The formatted excerpt markup.
   */
  public function getExcerptMarkup($excerpt) {
    return elasticsearch_helper_content_get_excerpt_markup($excerpt);
  }

  /**
   * Assembles an excerpt placeholder string.
   *
   * @return string
   *   The excerpt placeholder string.
   */
  public function getExcerptPlaceholder() {
    return elasticsearch_helper_content_get_excerpt_placeholder();
  }

}
