<?php

namespace Drupal\elasticsearch_helper_content\Plugin\ElasticsearchNormalizer\Field;

use Drupal\Core\Field\FieldItemInterface;
use Drupal\elasticsearch_helper_content\ElasticsearchDataTypeDefinition;
use Drupal\elasticsearch_helper_content\ElasticsearchFieldNormalizerBase;

/**
 * @ElasticsearchFieldNormalizer(
 *   id = "image_path",
 *   label = @Translation("Image path"),
 *   field_types = {
 *     "image"
 *   },
 *   weight = -10
 * )
 */
class ImagePathNormalizer extends ElasticsearchFieldNormalizerBase {

  /**
   * {@inheritDoc}
   */
  public function getFieldItemValue(FieldItemInterface $item, array $context = []) {
    $image_path = [];

    if ($image = $item->entity) {
      $image_uri = $image->getFileUri();
      $image_path = parse_url(file_create_url($image_uri), PHP_URL_PATH);
    }

    return $image_path;
  }

  /**
   * {@inheritDoc}
   */
  public function getPropertyDefinitions() {
    return ElasticsearchDataTypeDefinition::create('keyword');
  }

}
