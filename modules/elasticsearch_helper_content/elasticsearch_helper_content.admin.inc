<?php

/**
 * @file
 * Administration functions for elasticsearch_helper_content.module.
 */

use Drupal\Core\Render\Element;

/**
 * Prepares variables for Elasticsearch helper content settings form table.
 *
 * @param array $variables
 */
function template_preprocess_elasticsearch_helper_content_settings_form_table(&$variables) {
  // Add a render element representing the bundle Elasticsearch settings form table.
  $element = $variables['element'];

  $header = [
    [
      'data' => t('Index'),
      'class' => ['index'],
    ],
    [
      'data' => $element['#bundle_label'],
      'class' => ['bundle'],
    ],
    [
      'data' => t('Configuration'),
      'class' => ['configuration'],
    ],
  ];

  $rows = [];

  // Loop over bundles.
  foreach (Element::children($element) as $bundle) {
    $rows[$bundle] = [
      'data' => [
        [
          'data' => ['#title' => NULL] + $element[$bundle]['index'],
          'class' => ['index'],
        ],
        [
          'data' => $element[$bundle]['index']['#title'],
          'class' => ['bundle'],
        ],
        [
          'data' => $element[$bundle]['normalizer'],
          'class' => ['configuration'],
        ],
      ],
      'class' => ['bundle-settings'],
    ];

    // Loop over bundle fields.
    if (!empty($element[$bundle]['fields'])) {
      foreach (Element::children($element[$bundle]['fields']) as $field_name) {
        $rows[] = [
          'data' => [
            [
              'data' => ['#title' => NULL] + $element[$bundle]['fields'][$field_name]['index'],
              'class' => ['index'],
            ],
            [
              'data' => $element[$bundle]['fields'][$field_name]['index']['#title'],
              'class' => ['field'],
            ],
            [
              'data' => $element[$bundle]['fields'][$field_name]['normalizer'],
              'class' => ['configuration'],
            ],
          ],
          'class' => ['field-settings'],
        ];
      }
    }
  }

  $variables['title'] = $element['#title'];
  $variables['build'] = [
    '#header' => $header,
    '#rows' => $rows,
    '#type' => 'table',
  ];
}
