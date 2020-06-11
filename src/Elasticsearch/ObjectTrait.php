<?php

namespace Drupal\elasticsearch_helper\Elasticsearch;

use Drupal\Component\Utility\NestedArray;

/**
 * Provides helper methods to object definition classes.
 */
trait ObjectTrait {

  /**
   * @var array
   */
  protected $options = [];

  /**
   * Returns new instance.
   *
   * @return static
   */
  public static function create() {
    $instance = new static();

    return $instance;
  }

  /**
   * Adds an option.
   *
   * @param $option
   * @param $value
   *
   * @throws \InvalidArgumentException
   *
   * @return $this
   */
  public function addOption($option, $value) {
    $options = [$option => $value];
    $this->validateOptions($options);
    $this->options = NestedArray::mergeDeep($this->options, $options);

    return $this;
  }

  /**
   * Adds options.
   *
   * @param array $options
   *
   * @return $this
   *
   * @throws \InvalidArgumentException
   */
  public function addOptions(array $options) {
    $this->validateOptions($options);
    $this->options = NestedArray::mergeDeep($this->options, $options);

    return $this;
  }

  /**
   * Returns an option.
   *
   * @param $option
   *
   * @return mixed|null
   */
  public function getOption($option) {
    return isset($this->options[$option]) ? $this->options[$option] : NULL;
  }

  /**
   * Returns options.
   *
   * @return array
   */
  public function getOptions() {
    return $this->options;
  }

  /**
   * Returns object as an array.
   */
  public function toArray() {
    return $this->getOptions();
  }

  /**
   * Validates provided options.
   *
   * @param array $options
   *
   * @throws \InvalidArgumentException
   */
  protected function validateOptions(array $options) {
  }

}
