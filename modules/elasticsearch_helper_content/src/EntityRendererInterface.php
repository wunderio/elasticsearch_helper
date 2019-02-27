<?php

namespace Drupal\elasticsearch_helper_content;

use Drupal\Core\Entity\ContentEntityInterface;

/**
 * Interface EntityRendererInterface
 */
interface EntityRendererInterface {

  /**
   * Renders the entity and returns it as plain text.
   *
   * @param \Drupal\Core\Entity\ContentEntityInterface $entity
   * @param $view_mode
   *
   * @return string The rendered content as a string stripped of HTML tags.
   */
  public function renderEntityPlainText(ContentEntityInterface $entity, $view_mode);

  /**
   * Renders a content to a string using given view mode.
   *
   * @param \Drupal\Core\Entity\ContentEntityInterface $entity
   * @param $view_mode
   *
   * @return string
   */
  public function renderEntity(ContentEntityInterface $entity, $view_mode);

}
