<?php

namespace Drupal\elasticsearch_helper_instant\Plugin\Block;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Url;
use Drupal\Core\Block\BlockBase;

/**
 * Provides a 'ElasticsearchInstantSearchBlock' block.
 *
 * @Block(
 *  id = "elasticsearch_helper_instant",
 *  admin_label = @Translation("Elasticsearch instant search"),
 * )
 */
class ElasticsearchInstantSearchBlock extends BlockBase {

  /**
   * {@inheritdoc}
   */
  protected function blockAccess(AccountInterface $account) {
    return AccessResult::allowedIfHasPermission($account, 'access elasticsearch instant search');
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $build = [
      '#type' => 'container',
      '#attributes' => [
        'class' => ['instant-search'],
      ],
      '#attached' => [
        'library' => [
          'elasticsearch_helper_instant/instant-search',
        ],
        'drupalSettings' => [
          'elasticsearchInstantSearch' => [
            'remoteSource' => Url::fromRoute('elasticsearch_helper_instant.search')->toString(),
          ],
        ],
      ],
    ];

    $build['icon'] = [
      '#markup' => '<a href="#" class="instant-search__trigger"></a>',
    ];

    $build['search'] = [
      '#type' => 'container',
      '#attributes' => [
        'class' => ['instant-search__overlay'],
      ],
      'inner' => [
        '#type' => 'container',
        '#attributes' => [
          'class' => ['instant-search__inner'],
        ],
        'close' => [
          '#markup' => '<a href="#" class="instant-search__close"></a>',
        ],
        'input' => [
          '#theme' => 'input__textfield',
          '#attributes' => [
            'type' => 'text',
            'class' => ['instant-search__input'],
            'placeholder' => $this->t('Search…'),
          ],
        ],
      ],
    ];

    return $build;
  }

}
