<?php

namespace Drupal\elasticsearch_helper_content\Plugin\ElasticsearchNormalizer\Field;

use Drupal\Core\Field\FieldItemInterface;
use Drupal\elasticsearch_helper_content\ElasticsearchDataTypeDefinition;
use Drupal\elasticsearch_helper_content\ElasticsearchFieldNormalizerBase;
use Drupal\file\Entity\File;

/**
 * @ElasticsearchFieldNormalizer(
 *   id = "field_image",
 *   label = @Translation("Image path"),
 *   field_types = {
 *     "image"
 *   },
 *   weight = -10
 * )
 */
class ElasticsearchFieldImagePathNormalizer extends ElasticsearchFieldNormalizerBase {

  /**
   * {@inheritDoc}
   */
  public function getFieldItemValue(FieldItemInterface $item, array $context = []) {
    $fid = $item->get('target_id')->getValue();
    $image = File::load($fid);
    $image_uri = $image->getFileUri();
    $image_path = parse_url(file_create_url($image_uri), PHP_URL_PATH);
    return $image_path;
  }

  /**
   * {@inheritDoc}
   */
  public function getPropertyDefinitions() {
    return ElasticsearchDataTypeDefinition::create('keyword');
  }

}
