<?php

namespace Drupal\elasticsearch_helper_content\Plugin\views\field;

use Drupal\elasticsearch_helper_views\Plugin\views\field\SourceValueTrait;
use Drupal\views\Plugin\views\field\NumericField;

/**
 * Renders a field as a numeric value.
 *
 * @ingroup views_field_handlers
 *
 * @ViewsField("elasticsearch_content_numeric")
 */
class Numeric extends NumericField {

  use SourceValueTrait;

}
