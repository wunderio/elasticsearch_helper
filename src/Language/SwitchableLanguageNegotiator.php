<?php

namespace Drupal\elasticsearch_helper\Language;

use Drupal\language\LanguageNegotiator;

/**
 * Allows switching the current language to another language.
 */
class SwitchableLanguageNegotiator extends LanguageNegotiator {

  /**
   * Language code.
   *
   * @var string|null
   */
  protected $languageCode = NULL;

  /**
   * {@inheritdoc}
   */
  public function initializeType($type) {
    $result = NULL;
    $languages = $this->languageManager->getLanguages();

    if ($this->languageCode && isset($languages[$this->languageCode])) {
      $result = $languages[$this->languageCode];
    }
    else {
      $result = $this->languageManager->getDefaultLanguage();
    }

    return [static::METHOD_ID => $result];
  }

  /**
   * Sets language code.
   *
   * @param string $langcode
   */
  public function setLanguageCode($langcode) {
    $this->languageCode = $langcode;
  }

}
