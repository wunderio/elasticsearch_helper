<?php

namespace Drupal\elasticsearch_helper_content\Plugin\Normalizer;

use Drupal\Core\Entity\EntityDisplayRepositoryInterface;
use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Entity\EntityTypeBundleInfo;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\EntityTypeRepositoryInterface;
use Drupal\Core\Language\LanguageManager;
use Drupal\Core\TypedData\TranslatableInterface;
use Drupal\serialization\Normalizer\ContentEntityNormalizer;
use Drupal\Core\Theme\ThemeManager;
use Drupal\Core\Theme\ThemeInitialization;
use Drupal\core\Entity\ContentEntityBase;
use Drupal\Core\Render\Renderer;
use Drupal\Core\Config\ConfigFactory;
use Drupal\Core\Site\Settings;

/**
 * Normalizes / denormalizes Drupal nodes into an array structure good for ES.
 */
class ElasticsearchContentNormalizer extends ContentEntityNormalizer {

  /**
   * View mode to use for general textual content field.
   *
   * @var string
   */
  protected $contentViewMode = 'search_index';

  /**
   * View mode to use for search result snipped field.
   *
   * @var string
   */
  protected $searchResultViewMode = 'search_result';

  /**
   * The interface or class that this Normalizer supports.
   *
   * @var array
   */
  protected $supportedInterfaceOrClass = ['Drupal\Core\Entity\ContentEntityInterface'];

  /**
   * Supported formats.
   *
   * @var array
   */
  protected $format = ['elasticsearch_helper'];

  /**
   *  The entity type manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The config.factory service.
   *
   * @var \Drupal\Core\Config\ConfigFactory
   */
  protected $configFactory;

  /**
   * The renderer service.
   *
   * @var \Drupal\Core\Render\Renderer
   */
  protected $renderer;

  /**
   * The theme.initialization service.
   *
   * @var \Drupal\Core\Theme\ThemeInitialization
   */
  protected $themeInitialization;

  /**
   * The theme.manager service.
   *
   * @var \Drupal\Core\Theme\ThemeManager
   */
  protected $themeManager;

  /**
   * The language_manager service.
   *
   * @var \Drupal\Core\Language\LanguageManager
   */
  protected $languageManager;

  /**
   * The  entity_type.bundle.info service.
   *
   * @var \Drupal\Core\Entity\EntityTypeBundleInfo
   */
  protected $entityTypeBundleInfo;

  /**
   * The entity display repository.
   *
   * @var \Drupal\Core\Entity\EntityDisplayRepositoryInterface
   */
  protected $entityDisplayRepository;

  /**
   * Constructs an ElasticsearchContentNormalizer object.
   */
  public function __construct(
    EntityTypeManagerInterface $entity_type_manager,
    EntityTypeRepositoryInterface $entity_type_repository,
    EntityFieldManagerInterface $entity_field_manager,
    ConfigFactory $config_factory,
    Renderer $renderer,
    ThemeManager $theme_manager,
    ThemeInitialization $theme_initialization,
    LanguageManager $language_manager,
    EntityTypeBundleInfo $entity_type_bundle_info,
    EntityDisplayRepositoryInterface $entity_display_repository
  ) {
    parent::__construct($entity_type_manager, $entity_type_repository, $entity_field_manager);
    $this->entityTypeManager = $entity_type_manager;
    $this->configFactory = $config_factory;
    $this->renderer = $renderer;
    $this->themeManager = $theme_manager;
    $this->themeInitialization = $theme_initialization;
    $this->languageManager = $language_manager;
    $this->entityTypeBundleInfo = $entity_type_bundle_info;
    $this->entityDisplayRepository = $entity_display_repository;
  }

  /**
   * {@inheritdoc}
   */
  public function normalize($object, $format = NULL, array $context = []) {
    // Get all the default properties in.
    $data = [];
    if (!$this->getSetting('skip_default_normalize', FALSE)) {
      $data += parent::normalize($object, $format, $context);
    }

    $bundle_infos = $this->entityTypeBundleInfo->getBundleInfo($object->getEntityTypeId());

    // Switch config language to current entity language.
    // This _should_ work, but doesn't, very probably due to a D8 core bug.
    // Still kept in as a reminder that this needed and _has_ to work.
    $langcode = $object->language()->getId();
    $language = $this->languageManager->getLanguage($langcode);
    $original_language = $this->languageManager->getConfigOverrideLanguage();
    $this->languageManager->setConfigOverrideLanguage($language);

    try {
      // Add some common properties that will have ES field mappings defined.
      /** @var \Drupal\Core\Entity\ContentEntityBase $object */
      $data['id'] = $object->id();
      $data['uuid'] = $object->uuid();
      $data['entity'] = $object->getEntityTypeId();
      $data['bundle'] = $object->bundle();
      // @Todo How to get labels of entity type and bundle in current language?
      $data['entity_label'] = $object->getEntityType()->getLabel();
      $data['bundle_label'] = $bundle_infos[$object->bundle()]['label'];
      $data['label'] = $object->label();
      $data['url_internal'] = $object->toUrl()->getInternalPath();
      $data['url_alias'] = $object->toUrl()->toString();
      $data['created'] = $object->hasField('created') ? $object->created->value : NULL;
      // No status field => assume 1 to simplify filtering cross entity types.
      $data['status'] = $object->hasField('status') ? boolval($object->status->value) : TRUE;

      // Add full plain text render of $object in specific view mode.
      // This view mode can be configured to contain all relevant output.
      // Use this field for full regular text search.
      $data['content'] = $this->renderEntityPlainText($object, $this->getContentViewMode($object, $format, $context));

      // Add full markup render of $object in search_result view mode.
      // This view mode can be configured for display of search results.
      // Query and display this field for snappy results display in frontend.
      $data['rendered_search_result'] = $this->renderEntity($object, $this->getSearchResultViewMode($object, $format, $context));
    }
    finally {
      // Revert the interface language.
      $this->languageManager->setConfigOverrideLanguage($original_language);
    }

    return $data;
  }

  /**
   * Renders a content to a string.
   *
   * @param \Drupal\core\Entity\ContentEntityBase $entity
   *   The node that needs to rendered.
   * @param string $viewmode
   *   The viewmode in which to render the entity as plain text.
   *
   * @return string
   *   The rendered content as a string stripped of HTML tags.
   */
  private function renderEntityPlainText(ContentEntityBase $entity, $viewmode) {
    $render_markup = $this->renderEntity($entity, $viewmode);
    // Add some non-html spacing for improved excerpt highlighting.
    $render_markup = strtr($render_markup, [
      '</p>' => "</p>\n\n",
      '</div>' => "</div>\n\n\n",
    ]);
    return preg_replace('/\s+/', ' ', strip_tags($render_markup));
  }

  /**
   * Renders a content to a string using a given view mode.
   *
   * @param \Drupal\core\Entity\ContentEntityBase $entity
   *   The node that needs to rendered.
   * @param $view_mode
   *   The id of thee view_mode.
   *
   * @return string
   *   The rendered content as a string.
   */
  protected function renderEntity(ContentEntityBase $entity, $view_mode) {
    $render_markup = '';

    $current_active_theme = $this->themeManager->getActiveTheme();
    // Load the theme object for the theme.
    $frontend_theme = $this->themeInitialization->initTheme($this->getRenderTheme());
    // Switch the theme. This is needed as a renderer might be called from
    // hook_entity_update in a context with the backend theme active.
    // See MailsystemManager::mail() for a core example.
    $this->themeManager->setActiveTheme($frontend_theme);
    // TODO Prio 1: Set config language to content language, so labels,dates
    //              etc. will be rendered in the correct target language.
    // TODO Prio 3: Deactivate any twig debugging that might be active.

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
      $this->themeManager->setActiveTheme($current_active_theme);
    }

    return $render_markup;
  }

  /**
   * Renders the entity in the given view mode and lang code.
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
   * viewmode one after another and concatenating their render array outputs,
   * with the special treatment of paragraph/entity_reference_revision fields
   * being rendered field item by field item.
   *
   * This workaround is only used in the situations where it's necessary.
   * (a translatable entity with entitiy_reference_revision field referencing
   *  paragraph entities, about to be rendered in a non-current-content-lang)
   *
   * This partially active workaround results in limitations and possibly
   * deviant render behaviour, since it allows only core display settings for
   * the requested viewmode, and e.g. not display suite managed settings.
   *
   * @param \Drupal\core\Entity\ContentEntityBase $entity
   *   The entity to render.
   * @param string $view_mode
   *   The viewmode to render in.
   * @param string $langcode
   *   The langcode of the language to render in.
   *
   * @return array
   *   The generated render array.
   */
  public function renderEntityHelper(ContentEntityBase $entity, $view_mode, $langcode) {
    // @Todo Check what happens if $view_mode has no explicit settings.
    //       (I.e. when "default" should be used => is this working automatically?)
    /** @var \Drupal\Core\Entity\Display\EntityViewDisplayInterface $display */
    $display = $this->entityDisplayRepository->getViewDisplay($entity->getEntityTypeId(), $entity->bundle(), $view_mode);
    $display_components = $display->getComponents();
    uasort($display_components, function($a, $b) { return $a['weight'] - $b['weight']; });
    $build = [];

    // Prepare decision criteria.
    $entity_translatable = ($entity instanceof TranslatableInterface) && $entity->isTranslatable();
    $langcode_non_current = $langcode != $this->languageManager->getCurrentLanguage()->getId();
    $entity_has_er_revisions = array_reduce($display_components, function ($c, $it) {
      return (isset($it['type']) && ($it['type'] == 'entity_reference_revisions_entity_view')) ? TRUE : $c;
    }, FALSE);

    // If we have our special case, ...
    if ($entity_translatable && $langcode_non_current && $entity_has_er_revisions) {
      // .. go through all the fields relevant for the given viewmode ...
      foreach ($display_components as $component_name => $component_info) {
        $display_settings = $display_components[$component_name]['settings'];

        if (!isset($display->hidden[$component_name])) {
          // ... and either render any er_revisions field via a helper function.
          if ($component_info['type'] == 'entity_reference_revisions_entity_view') {
            $build[] = $this->renderEntityHelperFieldItems($entity->get($component_name)->referencedEntities(), $display_settings, $langcode);
          }
          // ... or render the field as whole.
          else {
            $build[] = $entity->get($component_name)->view($display_settings);
          }
        }
      }
    }
    // ... or just do a plain old entity render.
    else {
      $view_builder = $this->entityTypeManager
        ->getViewBuilder($entity->getEntityTypeId());
      $build = $view_builder->view($entity, $view_mode, $langcode);
    }

    return $build;
  }

  /**
   * Renders a entity_reference_revisions field's entities.
   *
   * Only meant for use in workaround/helper function renderEntityHelper.
   *
   * @param array $ref_entities
   *   The referenced entities to render.
   * @param string $display_settings
   *   The display settings to use for rendering.
   * @param string $langcode
   *   Langcode to render referenced entities in.
   *
   * @return array
   *   Generated render array.
   */
  protected function renderEntityHelperFieldItems(array $ref_entities, $display_settings, $langcode) {
    // Render each field item of a entity-reference-revision field individually.
    $build_container = [];

    foreach ($ref_entities as $ref_entity) {
      // @Todo use dependency injected $this->entityTypeManager;
      $view_builder = \Drupal::entityTypeManager()->getViewBuilder($ref_entity->getEntityTypeId());
      $ref_entity_viewmode = $display_settings['view_mode'];
      $build_container[] = $view_builder->view($ref_entity, $ref_entity_viewmode, $langcode);
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
    return $this->getSetting('render_theme', $this->configFactory->get('system.theme')->get('default'));
  }

  /**
   * Returns a settings key's value.
   *
   * @param string $key
   *   The key to which to return the settings value for.
   * @param mixed $default
   *   The value of the settings key.
   *
   * @return string
   */
  public function getSetting($key, $default = NULL) {
    $value = $default;
    $settings = Settings::get('elasticsearch_helper_content');
    if (is_array($settings) && isset($settings[$key])) {
      $value = $settings[$key];
    }
    return $value;
  }

  /**
   * Returns view mode to use for textual content.
   *
   * @param $object
   * @param null $format
   * @param array $context
   *
   * @return string
   */
  protected function getContentViewMode($object, $format = NULL, array $context = []) {
    return $this->contentViewMode;
  }

  /**
   * Returns view mode to use for search result snippet.
   *
   * @param $object
   * @param null $format
   * @param array $context
   *
   * @return string
   */
  protected function getSearchResultViewMode($object, $format = NULL, array $context = []) {
    return $this->searchResultViewMode;
  }

}
