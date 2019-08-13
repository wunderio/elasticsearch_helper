<?php

namespace Drupal\elasticsearch_helper_content\Plugin\ElasticsearchExtraField;

use Drupal\elasticsearch_helper_content\ElasticsearchExtraFieldBase;
use Drupal\elasticsearch_helper_content\ElasticsearchExtraFieldInterface;
use Drupal\elasticsearch_helper_content\ElasticsearchField;

/**
 * @ElasticsearchExtraField(
 *   id = "rendered_content",
 *   label = @Translation("Rendered content")
 * )
 */
class RenderedContent extends ElasticsearchExtraFieldBase implements ElasticsearchExtraFieldInterface {

  /**
   * {@inheritdoc}
   */
  public function getFields() {
    return [
      new ElasticsearchField('rendered_content', t('Rendered content'), 'rendered_content'),
    ];
  }

}
