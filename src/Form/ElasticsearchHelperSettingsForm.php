<?php

namespace Drupal\elasticsearch_helper\Form;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Elasticsearch\Client;
use Elasticsearch\Common\Exceptions\NoNodesAvailableException;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class ElasticsearchHelperSettingsForm.
 *
 * @package Drupal\elasticsearch_helper\Form
 */
class ElasticsearchHelperSettingsForm extends ConfigFormBase {

  /**
   * The Elasticsearch client.
   *
   * @var \Elasticsearch\Client
   */
  protected $client;

  /**
   * ElasticsearchHelperSettingsForm constructor.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The factory for configuration objects.
   * @param \Elasticsearch\Client $elasticsearch_client
   *   The Elasticsearch client.
   */
  public function __construct(ConfigFactoryInterface $config_factory, Client $elasticsearch_client) {
    parent::__construct($config_factory);

    $this->client = $elasticsearch_client;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('elasticsearch_helper.elasticsearch_client')
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

    try {
      $health = $this->client->cluster()->health();

      $this->messenger()->addStatus($this->t('Connected to Elasticsearch'));

      $color_states = [
        'green' => 'status',
        'yellow' => 'warning',
        'red' => 'error',
      ];

      $this->messenger()->addMessage($this->t('Elasticsearch cluster status is @status', [
        '@status' => $health['status'],
      ]), $color_states[$health['status']]);
    }
    catch (NoNodesAvailableException $e) {
      $this->messenger()->addError($this->t('Could not connect to Elasticsearch'));
    }
    catch (\Exception $e) {
      $this->messenger()->addError($e->getMessage());
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
      ],
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

    $form['defer_indexing'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Defer indexing'),
      '#description' => $this->t('Defer indexing to a queue worker instead of indexing immediately. This can be useful when importing very large amounts of Drupal entities.'),
      '#default_value' => (int) $config->get('elasticsearch_helper.defer_indexing'),
    ];

    return parent::buildForm($form, $form_state);
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
      ->set('elasticsearch_helper.defer_indexing', $form_state->getValue('defer_indexing'))
      ->save();
  }

}
