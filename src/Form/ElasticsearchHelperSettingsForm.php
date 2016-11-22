<?php

namespace Drupal\elasticsearch_helper\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Elasticsearch\Client;
use Elasticsearch\Common\Exceptions\NoNodesAvailableException;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Config\ConfigManager;

/**
 * Class ElasticsearchHelperSettingsForm.
 *
 * @package Drupal\elasticsearch_helper\Form
 */
class ElasticsearchHelperSettingsForm extends ConfigFormBase {

  /**
   * Drupal\Core\Config\ConfigManager definition.
   *
   * @var Drupal\Core\Config\ConfigManager
   */
  protected $configManager;
  public function __construct(
    ConfigFactoryInterface $config_factory,
      ConfigManager $config_manager
    ) {
    parent::__construct($config_factory);
        $this->configManager = $config_manager;
  }

  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
            $container->get('config.manager')
    );
  }

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


    $form['host'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Host'),
      '#maxlength' => 64,
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
      ->set('elasticsearch_helper.host', $form_state->getValue('host'))
      ->set('elasticsearch_helper.port', $form_state->getValue('port'))
      ->set('elasticsearch_helper.authentication', $form_state->getValue('authentication'))
      ->set('elasticsearch_helper.user', $form_state->getValue('user'))
      ->set('elasticsearch_helper.password', $form_state->getValue('password'))
      ->save();
  }

}
