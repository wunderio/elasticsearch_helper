<?php

namespace Drupal\elasticsearch_helper\Plugin\ElasticsearchAuth;

use Drupal\Core\Form\FormStateInterface;
use Drupal\elasticsearch_helper\Plugin\ElasticsearchAuthPluginBase;
use Elasticsearch\ClientBuilder;

/**
 * Basic auth authentication.
 *
 * @ElasticsearchAuth(
 *   id = "basic_auth",
 *   label = @Translation("Basic auth"),
 *   description = @Translation("Authentication with a username and password."),
 *   weight = 0
 * )
 */
class BasicAuth extends ElasticsearchAuthPluginBase {

  /**
   * {@inheritdoc}
   */
  public function authenticate(ClientBuilder $client_builder) {
    // Set basic auth credentials.
    $client_builder->setBasicAuthentication($this->configuration['user'], $this->configuration['password']);
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'user' => '',
      'password' => '',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form['user'] = [
      '#type' => 'textfield',
      '#title' => $this->t('User'),
      '#description' => $this->t('Enter Elasticsearch built-in or native user name.'),
      '#default_value' => $this->configuration['user'],
      '#size' => 32,
    ];

    $form['password'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Password'),
      '#description' => $this->t('Enter user password.'),
      '#default_value' => $this->configuration['password'],
      '#size' => 32,
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(&$form, FormStateInterface $form_state) {
    $this->configuration['user'] = $form_state->getValue('user');
    $this->configuration['password'] = $form_state->getValue('password');
  }

}
