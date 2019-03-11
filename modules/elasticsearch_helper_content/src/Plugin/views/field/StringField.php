<?php

namespace Drupal\elasticsearch_helper_content\Plugin\views\field;

use Drupal\elasticsearch_helper_views\Plugin\views\field\SourceValueTrait;
use Drupal\views\Plugin\views\field\FieldPluginBase;

/**
 * Renders a field as a string.
 *
 * @ingroup views_field_handlers
 *
 * @ViewsField("elasticsearch_content_string")
 */
class StringField extends FieldPluginBase {

  use SourceValueTrait;

}
