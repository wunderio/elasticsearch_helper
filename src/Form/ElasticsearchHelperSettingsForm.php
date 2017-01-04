<?php

namespace Drupal\elasticsearch_helper\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Elasticsearch\Client;
use Elasticsearch\Common\Exceptions\NoNodesAvailableException;

/**
 * Class ElasticsearchHelperSettingsForm.
 *
 * @package Drupal\elasticsearch_helper\Form
 */
class ElasticsearchHelperSettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'elasticsearch_helper.settings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'elasticsearch_helper_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('elasticsearch_helper.settings');

    /** @var Client $client */
    $client = \Drupal::service('elasticsearch_helper.elasticsearch_client');

    try {
      $health = $client->cluster()->health();

      drupal_set_message($this->t('Connected to Elasticsearch'));

      $color_states = [
        'green' => 'status',
        'yellow' => 'warning',
        'red' => 'error',
      ];

      drupal_set_message($this->t('Elasticsearch cluster status is @status', [
        '@status' => $health['status']
      ]), $color_states[$health['status']]);
    }
    catch (NoNodesAvailableException $e) {
      drupal_set_message('Could not connect to Elasticsearch', 'error');
    }
    catch (\Exception $e) {
      drupal_set_message($e->getMessage(), 'error');
    }

    $form['scheme'] = [
      '#type' => 'select',
      '#title' => $this->t('Scheme'),
      '#options' => [
        'http' => 'http',
        'https' => 'https',
      ],
      '#default_value' => $config->get('elasticsearch_helper.scheme'),
    ];

    $form['host'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Host'),
      '#size' => 32,
      '#default_value' => $config->get('elasticsearch_helper.host'),
    ];
    $form['port'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Port'),
      '#maxlength' => 4,
      '#size' => 4,
      '#default_value' => $config->get('elasticsearch_helper.port'),
    ];

    $form['authentication'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Use authentication'),
      '#default_value' => (int) $config->get('elasticsearch_helper.authentication'),
    ];

    $form['credentials'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Basic authentication'),
      '#states' => [
        'visible' => [
          ':input[name="authentication"]' => ['checked' => TRUE],
        ],
      ]
    ];

    $form['credentials']['user'] = [
      '#type' => 'textfield',
      '#title' => $this->t('User'),
      '#default_value' => $config->get('elasticsearch_helper.user'),
      '#size' => 32,
    ];

    $form['credentials']['password'] = [
      '#type' => 'textfield',
      '#title' => $this->t('password'),
      '#default_value' => $config->get('elasticsearch_helper.password'),
      '#size' => 32,
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);

    $this->config('elasticsearch_helper.settings')
      ->set('elasticsearch_helper.scheme', $form_state->getValue('scheme'))
      ->set('elasticsearch_helper.host', $form_state->getValue('host'))
      ->set('elasticsearch_helper.port', $form_state->getValue('port'))
      ->set('elasticsearch_helper.authentication', $form_state->getValue('authentication'))
      ->set('elasticsearch_helper.user', $form_state->getValue('user'))
      ->set('elasticsearch_helper.password', $form_state->getValue('password'))
      ->save();
  }

}
