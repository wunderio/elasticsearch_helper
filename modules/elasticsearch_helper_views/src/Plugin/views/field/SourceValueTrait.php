<?php

namespace Drupal\elasticsearch_helper_views\Plugin\views\field;

use Drupal\Component\Utility\NestedArray;
use Drupal\views\ResultRow;

/**
 * Trait SourceValueTrait
 *
 * This trait overrides value retrieval functions in core Views field plugins.
 */
trait SourceValueTrait {

  /**
   * @var string
   */
  protected $nestedValueSeparator = '.';

  /**
   * {@inheritdoc}
   */
  public function getValue(ResultRow $values, $field = NULL) {
    // Use field as a primary field name source.
    $alias = $field ?: NULL;
    // Use real field as secondary field name source.
    $alias = $alias ?: $this->realField;
    // Check if source element exists.
    $data = isset($values->_source) ? $values->_source : [];

    return $this->getNestedValue($alias, $data);
  }

  /**
   * Returns the value from the nested array.
   *
   * @param $key
   * @param array $data
   * @param $default
   *
   * @return mixed|null
   */
  protected function getNestedValue($key, array $data = [], $default = '') {
    $parts = explode($this->nestedValueSeparator, $key);

    if (count($parts) == 1) {
      return isset($data[$key]) ? $data[$key] : $default;
    }
    else {
      $value = NestedArray::getValue($data, $parts, $key_exists);
      return $key_exists ? $value : $default;
    }
  }

}
