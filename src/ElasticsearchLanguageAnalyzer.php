<?php

namespace Drupal\elasticsearch_helper;

/**
 * Class ElasticsearchLanguageAnalyzer.
 *
 * @package Drupal\elasticsearch_helper
 */
class ElasticsearchLanguageAnalyzer {

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
      'se' => 'swedish',
      'tr' => 'turkish',
      'th' => 'thai',

      // Use the built in CJK (Chinese, Japanese, Korean) analyzer by default.
      'zh-hans' => 'cjk',
      'ja' => 'cjk',
      'ko' => 'cjk',

      // For improved chinese support install the analysis-smartcn
      // elasticsearch plugin with the 'smartcn' analyzer.
      // For improved japanese support install the analysis-kuromoji
      // elasticsearch plugin with the 'kuromoji' analyzer.
    ];

    if (isset($language_analyzers[$langcode])) {
      return $language_analyzers[$langcode];
    }
    else {
      return 'standard';
    }
  }

}
