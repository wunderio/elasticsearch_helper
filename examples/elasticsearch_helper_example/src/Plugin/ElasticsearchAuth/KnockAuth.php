<?php

namespace Drupal\elasticsearch_helper_example\Plugin\ElasticsearchAuth;

use Drupal\Core\Form\FormStateInterface;
use Drupal\elasticsearch_helper\Plugin\ElasticsearchAuthPluginBase;
use Elasticsearch\ClientBuilder;

/**
 * Example auth authentication.
 *
 * @ElasticsearchAuth(
 *   id = "knock_auth",
 *   label = @Translation("Knock auth"),
 *   description = @Translation("Example plugin. Does not provide any real authentication method."),
 *   weight = 2
 * )
 */
class KnockAuth extends ElasticsearchAuthPluginBase {

  /**
   * {@inheritdoc}
   */
  public function authenticate(ClientBuilder $client_builder) {
    // Pseudo code.
    for ($i = 0; $i < $this->configuration['knock_times']; $i++) {
      // Imaginary method call of the client builder.
      // call_user_func([$client_builder, 'knockKnock'], $this->configuration['passcode']);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'knock_times' => 0,
      'passcode' => '',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form['knock_times'] = [
      '#type' => 'number',
      '#title' => t('Times to knock'),
      '#default_value' => $this->configuration['knock_times'],
      '#min' => 0,
      '#max' => 65535,
    ];

    $form['passcode'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Passcode'),
      '#description' => $this->t('Enter user passcode.'),
      '#default_value' => $this->configuration['passcode'],
      '#size' => 32,
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(&$form, FormStateInterface $form_state) {
    $this->configuration['knock_times'] = $form_state->getValue('knock_times');
    $this->configuration['passcode'] = $form_state->getValue('passcode');
  }

}
