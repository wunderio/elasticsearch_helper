<?php

namespace Drupal\elasticsearch_helper_content\Plugin\ElasticsearchNormalizer\Field;

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
class ImagePathNormalizer extends FilePathNormalizer {
}
