<?php

/**
 * @file
 * Administration functions for language.module.
 */

use Drupal\Core\Render\Element;
use Drupal\Core\Template\Attribute;

/**
 * Prepares variables for elasticsearch helper content settings table templates.
 *
 * Default template: elasticsearch-helper-content-settings-table.html.twig.
 *
 * @param array $variables
 *   An associative array containing:
 *   - element: An associative array containing the properties of the element.
 *     Properties used: #bundle_label, #title.
 */
function template_preprocess_elasticsearch_helper_content_settings_table(&$variables) {
  // Add a render element representing the bundle elasticsearch settings table.
  $element = $variables['element'];

  $header = [
    [
      'data' => $element['#bundle_label'],
      'class' => ['bundle'],
    ],
    [
      'data' => t('Configuration'),
      'class' => ['operations'],
    ],
  ];

  $rows = [];
  foreach (Element::children($element) as $bundle) {
    $rows[$bundle] = [
      'data' => [
        [
          'data' => [
            '#prefix' => '<label>',
            '#suffix' => '</label>',
            '#plain_text' => $element[$bundle]['settings']['#label'],
          ],
          'class' => ['bundle'],
        ],
        [
          'data' => $element[$bundle]['settings'],
          'class' => ['operations'],
        ],
      ],
      'class' => ['bundle-settings'],
    ];
  }

  $variables['title'] = $element['#title'];
  $variables['build'] = [
    '#header' => $header,
    '#rows' => $rows,
    '#type' => 'table',
  ];
}
