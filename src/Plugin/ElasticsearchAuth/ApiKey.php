<?php

namespace Drupal\elasticsearch_helper\Plugin\ElasticsearchAuth;

use Drupal\Core\Form\FormStateInterface;
use Drupal\elasticsearch_helper\Plugin\ElasticsearchAuthPluginBase;
use Elasticsearch\ClientBuilder;

/**
 * Basic auth authentication.
 *
 * @ElasticsearchAuth(
 *   id = "api_key",
 *   label = @Translation("API key"),
 *   description = @Translation("Authentication with an API key."),
 *   weight = 1
 * )
 */
class ApiKey extends ElasticsearchAuthPluginBase {

  /**
   * {@inheritdoc}
   */
  public function authenticate(ClientBuilder $client_builder) {
    // Set API key.
    $client_builder->setApiKey($this->configuration['id'], $this->configuration['api_key']);
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'id' => '',
      'api_key' => '',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form['id'] = [
      '#type' => 'textfield',
      '#title' => $this->t('ID'),
      '#description' => $this->t('Enter ID of the API key.'),
      '#default_value' => $this->configuration['id'],
      '#size' => 32,
    ];

    $form['api_key'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Key'),
      '#default_value' => $this->configuration['api_key'],
      '#description' => $this->t('Enter the API key.'),
      '#size' => 32,
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(&$form, FormStateInterface $form_state) {
    $this->configuration['id'] = $form_state->getValue('id');
    $this->configuration['api_key'] = $form_state->getValue('api_key');
  }

}
