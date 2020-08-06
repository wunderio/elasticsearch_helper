<?php

namespace Drupal\elasticsearch_helper\Form;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Elasticsearch\Client;
use Elasticsearch\Common\Exceptions\NoNodesAvailableException;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class ElasticsearchHelperSettingsForm
 */
class ElasticsearchHelperSettingsForm extends ConfigFormBase {

  /**
   * The Elasticsearch client.
   *
   * @var \Elasticsearch\Client
   */
  protected $client;

  /**
   * Configuration object.
   *
   * @var \Drupal\Core\Config\Config
   */
  protected $config;

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
    $this->config = $this->config('elasticsearch_helper.settings');
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
   * Returns host default settings.
   *
   * @return array
   */
  protected function getHostDefaultSettings() {
    return [
      'scheme' => 'http',
      'host' => NULL,
      'authentication' => [
        'enabled' => FALSE,
        'user' => NULL,
        'password' => NULL,
      ],
    ];
  }

  /**
   * Returns server health status.
   *
   * @return string
   *
   * @throws \Exception
   */
  protected function getServerHealthStatus() {
    $result = $this->client->cluster()->health();

    return $result['status'];
  }

  /**
   * Returns server health to message status mapping.
   *
   * @return array
   */
  protected function getHealthMessageMapping() {
    return [
      'green' => 'status',
      'yellow' => 'warning',
      'red' => 'error',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config;

    try {
      // Get server health status.
      $status = $this->getServerHealthStatus();

      $this->messenger()->addMessage($this->t('Connected to Elasticsearch'));

      // Get health status / message status mapping.
      $color_states = $this->getHealthMessageMapping();

      $this->messenger()->addMessage($this->t('Elasticsearch cluster status is @status', [
        '@status' => $status,
      ]), $color_states[$status]);
    }
    catch (NoNodesAvailableException $e) {
      $this->messenger()->addError($this->t('Could not connect to Elasticsearch'));
    }
    catch (\Exception $e) {
      $this->messenger()->addError($e->getMessage());
    }

    $form['hosts'] = [
      '#type' => 'table',
      '#header' => ['', $this->t('Scheme'), $this->t('Host'), $this->t('Port'), $this->t('Authentication'), $this->t('Weight')],
      '#empty' => $this->t('No hosts are defined.'),
      '#tabledrag' => [
        [
          'action' => 'order',
          'relationship' => 'sibling',
          'group' => 'weight',
        ],
      ],
      '#tree' => TRUE,
    ];

    foreach ($this->config->get('hosts') as $index => $host) {
      $form['hosts'][$index]['#attributes'] = ['class' => ['draggable'], 'id' => 'row-' . $index];

      $form['hosts'][$index]['handle'] = [];

      $form['hosts'][$index]['scheme'] = [
        '#type' => 'select',
        '#title' => $this->t('Scheme'),
        '#options' => [
          'http' => 'http',
          'https' => 'https',
        ],
        '#default_value' => $host['scheme'],
      ];

      $form['hosts'][$index]['host'] = [
        '#type' => 'textfield',
        '#title' => $this->t('Host'),
        '#default_value' => $host['host'],
      ];

      $form['hosts'][$index]['port'] = [
        '#type' => 'textfield',
        '#title' => $this->t('Port'),
        '#maxlength' => 4,
        '#size' => 4,
        '#default_value' => $host['port'],
      ];

      $form['hosts'][$index]['authentication']['enabled'] = [
        '#type' => 'checkbox',
        '#title' => $this->t('Use authentication'),
        '#default_value' => (int) $host['authentication']['enabled'],
      ];

      $form['hosts'][$index]['authentication']['credentials'] = [
        '#type' => 'fieldset',
        '#title' => $this->t('Basic authentication'),
        '#states' => [
          'visible' => [
            ':input[name="hosts[' . $index . '][authentication][enabled]"]' => ['checked' => TRUE],
          ],
        ],
      ];

      $form['hosts'][$index]['authentication']['credentials']['user'] = [
        '#type' => 'textfield',
        '#title' => $this->t('User'),
        '#default_value' => $host['authentication']['user'],
        '#size' => 32,
        '#parents' => ['hosts', $index, 'authentication', 'user'],
      ];

      $form['hosts'][$index]['authentication']['credentials']['password'] = [
        '#type' => 'textfield',
        '#title' => $this->t('password'),
        '#default_value' => $host['authentication']['password'],
        '#size' => 32,
        '#parents' => ['hosts', $index, 'authentication', 'password'],
      ];

      $form['hosts'][$index]['weight'] = [
        '#type' => 'textfield',
        '#default_value' => $index,
        '#attributes' => ['class' => ['weight']],
        '#title' => t('Weight'),
        '#title_display' => 'invisible',
      ];
    }

    $form['add_host'] = [
      '#type' => 'submit',
      '#value' => $this->t('Add host'),
      '#submit' => [[$this, 'addHostForm']],
    ];

    $form['defer_indexing'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Defer indexing'),
      '#description' => $this->t('Defer indexing to a queue worker instead of indexing immediately. This can be useful when importing very large amounts of Drupal entities.'),
      '#default_value' => (int) $config->get('defer_indexing'),
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * Adds new entry to the list of hosts.
   *
   * @param array $form
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   */
  public function addHostForm(array $form, FormStateInterface $form_state) {
    // Store submitted values.
    $this->storeSubmittedValues($form_state);

    // Add a new host.
    $hosts = $this->config->get('hosts');
    $hosts[] = $this->getHostDefaultSettings();
    $this->config->set('hosts', $hosts);

    $form_state->setRebuild();
  }

  /**
   * Stores submitted values.
   *
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   */
  public function storeSubmittedValues(FormStateInterface $form_state) {
    $this->config
      ->set('hosts', $form_state->getValue('hosts'))
      ->set('defer_indexing', $form_state->getValue('defer_indexing'));
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);

    // Store submitted values.
    $this->storeSubmittedValues($form_state);

    // Save submitted configuration values.
    $this->config->save();
  }

}
