<?php

namespace Drupal\elasticsearch_helper_content;

use Drupal\Core\Field\FieldItemInterface;

/**
 * Class FieldNormalizerBase
 */
abstract class ElasticsearchFieldNormalizerBase extends ElasticsearchNormalizerBase implements ElasticsearchFieldNormalizerInterface {

  /**
   * @var string
   */
  protected $targetEntityType;

  /**
   * @var string
   */
  protected $targetBundle;

  /**
   * ElasticsearchFieldNormalizerBase constructor.
   *
   * @param array $configuration
   * @param $plugin_id
   * @param $plugin_definition
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition) {
    if (!isset($configuration['entity_type'], $configuration['bundle'])) {
      throw new \InvalidArgumentException(t('Entity type or bundle key is not provided in plugin configuration.'));
    }

    $this->targetEntityType = $configuration['entity_type'];
    $this->targetBundle = $configuration['bundle'];
    unset($configuration['entity_type'], $configuration['bundle']);

    parent::__construct($configuration, $plugin_id, $plugin_definition);
  }

  /**
   * {@inheritdoc}
   */
  public function normalize($object, array $context = []) {
    $result = [];

    try {
      $cardinality = $this->getCardinality($object);

      foreach ($object as $item) {
        $value = $this->getFieldItemValue($item, $context);

        if ($cardinality === 1) {
          return $value;
        }

        // Do not pass empty strings.
        if ($value !== '') {
          $result[] = $value;
        }
      }
    }
    catch (\Exception $e) {
      watchdog_exception('elasticsearch_helper_content', $e);
    }

    return $result;
  }

  /**
   * @param \Drupal\Core\Field\FieldItemListInterface $item_list
   *
   * @return int
   */
  protected function getCardinality($item_list) {
    return $item_list->getFieldDefinition()->getFieldStorageDefinition()->getCardinality();
  }

  /**
   * Returns value of the field item.
   *
   * @param \Drupal\Core\Field\FieldItemInterface $item
   * @param array $context Context options for the normalizer
   *
   * @return mixed
   */
  public function getFieldItemValue(FieldItemInterface $item, array $context = []) {
    return '';
  }

}
