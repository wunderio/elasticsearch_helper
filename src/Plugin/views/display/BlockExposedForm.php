<?php

namespace Drupal\elasticsearch_helper_views\Plugin\views\display;

use Drupal\views\Plugin\views\display\Block as CoreViewsBlock;

/**
 * The plugin that handles a block with exposed form.
 *
 * @ingroup views_display_plugins
 *
 * @ViewsDisplay(
 *   id = "elasticsearch_block",
 *   title = @Translation("Block with exposed form"),
 *   help = @Translation("Display the view as a block with optional exposed form."),
 *   theme = "views_view",
 *   register_theme = FALSE,
 *   uses_hook_block = TRUE,
 *   contextual_links_locations = {"block"},
 *   admin = @Translation("Block with exposed form")
 * )
 *
 * @see \Drupal\views\Plugin\block\block\ViewsBlock
 * @see \Drupal\views\Plugin\Derivative\ViewsBlock
 */
class BlockExposedForm extends CoreViewsBlock {

  /**
   * {@inheritdoc}
   */
  public function usesExposedFormInBlock() {
    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function usesExposed() {
    return TRUE;
  }

}
