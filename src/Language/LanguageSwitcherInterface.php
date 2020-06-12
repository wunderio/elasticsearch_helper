<?php

namespace Drupal\elasticsearch_helper\Language;

/**
 * Defines language switcher interface.
 */
interface LanguageSwitcherInterface {

  /**
   * Switches current language instance to to given language code.
   *
   * It's important to call self::switchBack() after language switching
   * is no longer necessary.
   *
   * @param $langcode
   */
  public function switchTo($langcode);

  /**
   * Switches current language back to the original language.
   */
  public function switchBack();

}
