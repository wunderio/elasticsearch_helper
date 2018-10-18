<?php

/**
 * @file
 * This module provides tools to integrate elasticsearch with Drupal.
 */

/**
 * Implements hook_entity_insert().
 */
function elasticsearch_helper_forms() {
  $forms['elasticsearch_helper_settings_form'] = [
    'callback' => 'elasticsearch_helper_settings_form',
  ];
}

function elasticsearch_helper_settings_form($form, &$form_state) {
  $client = elasticsearch_helper_get_client();


  try {
    $health = $client->cluster()->health();

    drupal_set_message(t('Connected to Elasticsearch'));

    $color_states = [
      'green' => 'status',
      'yellow' => 'warning',
      'red' => 'error',
    ];

    drupal_set_message(t('Elasticsearch cluster status is @status', [
      '@status' => $health['status'],
    ]), $color_states[$health['status']]);
  }
  catch (NoNodesAvailableException $e) {
    drupal_set_message(t('Could not connect to Elasticsearch'), 'error');
  }
  catch (\Exception $e) {
    drupal_set_message($e->getMessage(), 'error');
  }


  $form['elasticsearch_helper_scheme'] = [
    '#type' => 'select',
    '#title' => t('Scheme'),
    '#options' => [
      'http' => 'http',
      'https' => 'https',
    ],
    '#default_value' => variable_get('elasticsearch_helper_scheme', 'http'),
  ];

  $form['elasticsearch_helper_host'] = [
    '#type' => 'textfield',
    '#title' => t('Host'),
    '#size' => 32,
    '#default_value' => variable_get('elasticsearch_helper_host', 'localhost'),
  ];
  $form['elasticsearch_helper_port'] = [
    '#type' => 'textfield',
    '#title' => t('Port'),
    '#maxlength' => 4,
    '#size' => 4,
    '#default_value' => variable_get('elasticsearch_helper_port', 9200),
  ];

  $form['elasticsearch_helper_authentication'] = [
    '#type' => 'checkbox',
    '#title' => t('Use authentication'),
    '#default_value' => (int) variable_get('elasticsearch_helper_authentication', 0),
  ];

  $form['credentials'] = [
    '#type' => 'fieldset',
    '#title' => t('Basic authentication'),
    '#states' => [
      'visible' => [
        ':input[name="elasticsearch_helper_authentication"]' => ['checked' => TRUE],
      ],
    ],
  ];

  $form['credentials']['elasticsearch_helper_user'] = [
    '#type' => 'textfield',
    '#title' => t('User'),
    '#default_value' => variable_get('elasticsearch_helper_user', ''),
    '#size' => 32,
  ];

  $form['credentials']['elasticsearch_helper_password'] = [
    '#type' => 'textfield',
    '#title' => t('Password'),
    '#default_value' => variable_get('elasticsearch_helper_password', ''),
    '#size' => 32,
  ];

  $form['elasticsearch_helper_defer_indexing'] = [
    '#type' => 'checkbox',
    '#title' => t('Defer indexing'),
    '#description' => t('Defer indexing to a queue worker instead of indexing immediately. This can be useful when importing very large amounts of Drupal entities.'),
    '#default_value' => (int) variable_get('elasticsearch_helper_defer_indexing', 0),
  ];
 
  return system_settings_form($form);

}