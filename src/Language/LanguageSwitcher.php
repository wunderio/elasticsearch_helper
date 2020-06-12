<?php

namespace Drupal\elasticsearch_helper\Language;

use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\language\LanguageNegotiatorInterface;

/**
 * Language switcher allows switching current language context.
 *
 * Similarly to AccountSwitcher service, this service switches the current
 * language to selected language and allow Drupal APIs to work with switched
 * language. When language is switched, the following things change:
 *
 * - t() method translates to switched language.
 * - Referenced content in entities are retrieved with switched language.
 * - Configuration is loaded in switched language.
 */
class LanguageSwitcher implements LanguageSwitcherInterface {

  /**
   * @var \Drupal\Core\Language\LanguageManagerInterface
   */
  protected $languageManager;

  /**
   * @var \Drupal\language\LanguageNegotiatorInterface
   */
  protected $currentNegotiator;

  /**
   * @var string|null
   */
  protected $currentLangcode = NULL;

  /**
   * @var string|null
   */
  protected $currentConfigOverrideLangcode = NULL;

  /**
   * LanguageSwitcher constructor.
   *
   * @param \Drupal\Core\Language\LanguageManagerInterface $language_manager
   * @param \Drupal\language\LanguageNegotiatorInterface $language_negotiator
   */
  public function __construct(LanguageManagerInterface $language_manager, LanguageNegotiatorInterface $language_negotiator) {
    $this->languageManager = $language_manager;

    // Store current language code and language negotiator.
    $this->currentLangcode = $this->languageManager->getCurrentLanguage()->getId();
    $this->currentConfigOverrideLangcode = $this->languageManager->getConfigOverrideLanguage()->getId();
    $this->currentNegotiator = $this->languageManager->getNegotiator();

    // Set switchable language negotiator.
    $this->languageManager->setNegotiator($language_negotiator);
  }

  /**
   * {@inheritdoc}
   */
  public function switchTo($langcode) {
    $this->languageManager->reset();
    /** @var \Drupal\language\LanguageNegotiatorInterface $language_negotiator */
    $language_negotiator = $this->languageManager->getNegotiator();
    $language_negotiator->setLanguageCode($langcode);

    // Set config override language.
    $config_override_language = $this->languageManager->getLanguage($langcode);
    $this->languageManager->setConfigOverrideLanguage($config_override_language);
  }

  /**
   * {@inheritdoc}
   */
  public function switchBack() {
    $this->languageManager->reset();

    /** @var \Drupal\language\LanguageNegotiatorInterface $language_negotiator */
    $language_negotiator = $this->languageManager->getNegotiator();
    $language_negotiator->setLanguageCode($this->currentLangcode);
    $this->languageManager->setNegotiator($this->currentNegotiator);

    // Restore config override language.
    $config_override_langcode = $this->currentConfigOverrideLangcode;
    $config_override_language = $this->languageManager->getLanguage($config_override_langcode);
    $this->languageManager->setConfigOverrideLanguage($config_override_language);
  }

}
