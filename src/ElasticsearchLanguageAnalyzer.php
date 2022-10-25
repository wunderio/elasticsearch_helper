<?php

namespace Drupal\elasticsearch_helper;

/**
 * Class ElasticsearchLanguageAnalyzer.
 */
class ElasticsearchLanguageAnalyzer {

  /**
   * Defines default analyzer.
   */
  const DEFAULT_ANALYZER = 'standard';

  /**
   * Get the name of a language analyzer for a given language code.
   *
   * @param string $langcode
   *   The language code for which to get the language analyzer.
   *
   * @return string
   *   The name of the Elasticsearch language analyzer to be used.
   */
  public static function get($langcode) {
    // Map language codes to the built-in language analysers documented here:
    // https://www.elastic.co/guide/en/elasticsearch/reference/current/analysis-lang-analyzer.html
    // Standard list of languages can be found in
    // \Drupal\Core\Language\LanguageManager::getStandardLanguageList().
    $language_analyzers = [
      'ar' => 'arabic',
      'hy' => 'armenian',
      'eu' => 'basque',
      'pt-br' => 'brazilian',
      'bg' => 'bulgarian',
      'ca' => 'catalan',
      'cs' => 'czech',
      'da' => 'danish',
      'nl' => 'dutch',
      'en' => 'english',
      'fi' => 'finnish',
      'fr' => 'french',
      'gl' => 'galician',
      'de' => 'german',
      'el' => 'greek',
      'et' => 'estonian',
      'hi' => 'hindi',
      'hu' => 'hungarian',
      'id' => 'indonesian',
      'ga' => 'irish',
      'it' => 'italian',
      'lv' => 'latvian',
      'lt' => 'lithuanian',
      'nb' => 'norwegian',
      'nn' => 'norwegian',
      'fa' => 'persian',
      'pt-pt' => 'portuguese',
      'ro' => 'romanian',
      'ru' => 'russian',
      'ku' => 'sorani',
      'es' => 'spanish',
      'sv' => 'swedish',
      'tr' => 'turkish',
      'th' => 'thai',

      // Use the built in CJK (Chinese, Japanese, Korean) analyzer by default.
      'zh-hans' => 'cjk',
      'ja' => 'cjk',
      'ko' => 'cjk',

      // For improved Chinese support install the analysis-smartcn
      // Elasticsearch plugin with the 'smartcn' analyzer.
      // For improved Japanese support install the analysis-kuromoji
      // Elasticsearch plugin with the 'kuromoji' analyzer.
    ];

    if (isset($language_analyzers[$langcode])) {
      return $language_analyzers[$langcode];
    }

    return self::DEFAULT_ANALYZER;
  }

}
