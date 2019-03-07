<?php

namespace Drupal\elasticsearch_helper_content;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\TranslatableInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\Render\RendererInterface;
use Drupal\Core\Theme\ThemeInitializationInterface;
use Drupal\Core\Theme\ThemeManagerInterface;

/**
 * Class EntityRenderer
 */
class EntityRenderer implements EntityRendererInterface {

  /**
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * @var \Drupal\Core\Render\RendererInterface
   */
  protected $renderer;

  /**
   * @var \Drupal\Core\Theme\ThemeManagerInterface
   */
  protected $themeManager;

  /**
   * @var \Drupal\Core\Theme\ThemeInitializationInterface
   */
  protected $themeInitialization;

  /**
   * @var \Drupal\Core\Language\LanguageManagerInterface
   */
  protected $languageManager;

  /**
   * EntityRenderer constructor.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   * @param \Drupal\Core\Render\RendererInterface $renderer
   * @param \Drupal\Core\Theme\ThemeManagerInterface $theme_manager
   * @param \Drupal\Core\Theme\ThemeInitializationInterface $theme_initialization
   * @param \Drupal\Core\Language\LanguageManagerInterface $language_manager
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, ConfigFactoryInterface $config_factory, RendererInterface $renderer, ThemeManagerInterface $theme_manager, ThemeInitializationInterface $theme_initialization, LanguageManagerInterface $language_manager) {
    $this->entityTypeManager = $entity_type_manager;
    $this->configFactory = $config_factory;
    $this->renderer = $renderer;
    $this->themeManager = $theme_manager;
    $this->themeInitialization = $theme_initialization;
    $this->languageManager = $language_manager;
  }

  /**
   * {@inheritdoc}
   */
  public function renderEntityPlainText(ContentEntityInterface $entity, $view_mode) {
    $result = $this->renderEntity($entity, $view_mode);
    // Add some non-html spacing for improved excerpt highlighting.
    $result = strtr($result, [
      '</p>' => "</p>\n\n",
      '</div>' => "</div>\n\n\n",
    ]);

    return preg_replace('/\s+/', ' ', strip_tags($result));
  }

  /**
   * {@inheritdoc}
   */
  public function renderEntity(ContentEntityInterface $entity, $view_mode) {
    $render_markup = '';

    // Load the theme object for the theme.
    $frontend_theme = $this->themeInitialization->initTheme($this->getRenderTheme());
    // Switch the theme. This is needed as a renderer might be called from
    // hook_entity_update in a context with the backend theme active.
    // See MailsystemManager::mail() for a core example.
    $this->themeManager->setActiveTheme($frontend_theme);
    // @todo Set config language to content language, so labels,dates
    // etc. will be rendered in the correct target language.
    // @todo Deactivate any twig debugging that might be active.

    // Render the entity.
    try {
      /** @var \Drupal\Core\Render\RendererInterface $renderer */
      // Get the object language.
      $langcode = $entity->language()->getId();
      // Build a render array and render it into markup.
      $render_array = $this->renderEntityHelper($entity, $view_mode, $langcode);
      $render_markup = $this->renderer->renderPlain($render_array);
    }
    finally {
      // Revert the active theme, this is done inside a finally block so it is
      // executed even if an exception is thrown during rendering the entity.
      $current_active_theme = $this->themeManager->getActiveTheme();
      $this->themeManager->setActiveTheme($current_active_theme);
    }

    return $render_markup;
  }

  /**
   * Renders the entity in the given view mode and language code.
   *
   * This is a workaround for situations where $view_builder->view(...) fails to
   * render all fields of an entity in the correct language, e.g. in the case of
   * paragraphs (referenced via an entity reference revisions field), when the
   * entity should be displayed in a language which is not the current content
   * language. This case results in those paragraphs's content be returned in
   * the wrong language or simply as empty (the latter in case they don't hold
   * content for the current content language) [as of 2018-02-21]
   * See e.g. https://www.drupal.org/project/paragraphs/issues/2753201
   *
   * The workaround consists of rendering those field configured in the given
   * view mode one after another and concatenating their render array outputs,
   * with the special treatment of paragraph/entity_reference_revision fields
   * being rendered field item by field item.
   *
   * This workaround is only used in the situations where it's necessary
   * (a translatable entity with entity_reference_revision field referencing
   * paragraph entities, about to be rendered in a non-current-content-lang).
   *
   * This partially active workaround results in limitations and possibly
   * deviant render behaviour, since it allows only core display settings for
   * the requested view mode, and e.g. not dDisplay Suite managed settings.
   *
   * @param \Drupal\Core\Entity\ContentEntityInterface $entity
   * @param string $view_mode
   * @param string $langcode
   *
   * @return array
   */
  public function renderEntityHelper(ContentEntityInterface $entity, $view_mode, $langcode) {
    $build = [];

    /** @var \Drupal\Core\Entity\Display\EntityViewDisplayInterface $display */
    foreach ([$view_mode, 'default'] as $view_mode) {
      if ($display = $this->entityTypeManager->getStorage('entity_view_display')->load($entity->getEntityTypeId() . '.' . $entity->bundle() . '.' . $view_mode)) {
        break;
      }
    }

    if (!$display) {
      return [];
    }

    $display_components = $display->getComponents();
    uasort($display_components, function($a, $b) { return $a['weight'] - $b['weight']; });

    // Prepare decision criteria.
    $entity_translatable = ($entity instanceof TranslatableInterface) && $entity->isTranslatable();
    $langcode_non_current = $langcode != $this->languageManager->getCurrentLanguage()->getId();
    $entity_has_entity_reference_revisions = array_reduce($display_components, function ($c, $it) {
      return (isset($it['type']) && ($it['type'] == 'entity_reference_revisions_entity_view')) ? TRUE : $c;
    }, FALSE);

    if ($entity_translatable && $langcode_non_current && $entity_has_entity_reference_revisions) {
      // Loop over view mode components (fields).
      foreach ($display_components as $component_name => $component_info) {
        $display_settings = $display_components[$component_name]['settings'];

        if (!isset($display->hidden[$component_name])) {
          // Handle entity reference revision fields separately.
          if ($component_info['type'] == 'entity_reference_revisions_entity_view') {
            $build[] = $this->renderEntityReferenceRevisionsField($entity->get($component_name)->referencedEntities(), $display_settings, $langcode);
          }
          else {
            $build[] = $entity->get($component_name)->view($display_settings);
          }
        }
      }
    }
    else {
      $view_builder = $this->entityTypeManager->getViewBuilder($entity->getEntityTypeId());
      $build = $view_builder->view($entity, $view_mode, $langcode);
    }

    return $build;
  }

  /**
   * Renders entity_reference_revisions field entities.
   *
   * @param \Drupal\Core\Entity\ContentEntityInterface[] $entities
   * @param array $display_settings
   * @param string $langcode
   *
   * @return array
   */
  protected function renderEntityReferenceRevisionsField(array $entities, array $display_settings, $langcode) {
    // Render each field item of a entity-reference-revision field individually.
    $build_container = [];

    foreach ($entities as $entity) {
      $view_builder = $this->entityTypeManager->getViewBuilder($entity->getEntityTypeId());
      $entity_view_mode = $display_settings['view_mode'];
      $build_container[] = $view_builder->view($entity, $entity_view_mode, $langcode);
    }

    if (!empty($build_container)) {
      $build_container['#type'] = 'container';
    }

    return $build_container;
  }

  /**
   * Determine the name of the theme that should be used for rendering.
   *
   * @return string
   *   The theme name.
   */
  public function getRenderTheme() {
    return $this->configFactory->get('system.theme')->get('default');
  }

}
