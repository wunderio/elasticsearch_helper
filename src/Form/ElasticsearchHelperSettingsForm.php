<?php

namespace Drupal\elasticsearch_helper\Form;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\elasticsearch_helper\ElasticsearchHost;
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

    $header = [
      '',
      $this->t('Scheme'),
      $this->t('Host'),
      $this->t('Port'),
      $this->t('Authentication'),
      $this->t('Weight'),
      $this->t('Remove'),
    ];

    $form['hosts'] = [
      '#type' => 'table',
      '#header' => $header,
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

    foreach ($this->getHosts($form_state) as $index => $host) {
      $host = ElasticsearchHost::createFromArray($host);

      $form['hosts'][$index]['#attributes'] = ['class' => ['draggable'], 'id' => 'row-' . $index];

      $form['hosts'][$index]['handle'] = [];

      $form['hosts'][$index]['scheme'] = [
        '#type' => 'select',
        '#title' => $this->t('Scheme'),
        '#options' => [
          'http' => 'http',
          'https' => 'https',
        ],
        '#default_value' => $host->getScheme(),
      ];

      $form['hosts'][$index]['host'] = [
        '#type' => 'textfield',
        '#title' => $this->t('Host'),
        '#default_value' => $host->getHost(),
      ];

      $form['hosts'][$index]['port'] = [
        '#type' => 'textfield',
        '#title' => $this->t('Port'),
        '#maxlength' => 4,
        '#placeholder' => ElasticsearchHost::PORT_DEFAULT,
        '#size' => 4,
        '#default_value' => $host->getPort(),
      ];

      $form['hosts'][$index]['authentication']['enabled'] = [
        '#type' => 'checkbox',
        '#title' => $this->t('Use authentication'),
        '#default_value' => (int) $host->isAuthEnabled(),
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
        '#default_value' => $host->getAuthUsername(),
        '#size' => 32,
        '#parents' => ['hosts', $index, 'authentication', 'user'],
      ];

      $form['hosts'][$index]['authentication']['credentials']['password'] = [
        '#type' => 'textfield',
        '#title' => $this->t('password'),
        '#default_value' => $host->getAuthPassword(),
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

      $form['hosts'][$index]['remove'] = [
        '#type' => 'submit',
        '#index' => $index,
        '#value' => t('Remove'),
        '#submit' => [[$this, 'removeHost']],
      ];
    }

    $form['add_host'] = [
      '#type' => 'submit',
      '#value' => $this->t('Add host'),
      '#submit' => [[$this, 'addHost']],
    ];

    $form['defer_indexing'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Defer indexing'),
      '#description' => $this->t('Defer indexing to a queue worker instead of indexing immediately. This can be useful when importing very large amounts of Drupal entities.'),
      '#default_value' => (int) $this->config->get('defer_indexing'),
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * Returns a list of host configurations.
   *
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *
   * @return array
   */
  protected function getHosts(FormStateInterface $form_state) {
    if ($form_state->isProcessingInput()) {
      $result = $form_state->get('hosts');
    }
    else {
      $result = $this->config->get('hosts');
    }

    return $result ?: [];
  }

  /**
   * Removes the host from the list.
   *
   * @param array $form
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   */
  public function removeHost(array $form, FormStateInterface $form_state) {
    // Store submitted host values.
    $this->copyHostValuesFromFormState($form_state);

    $triggering_element = $form_state->getTriggeringElement();
    $index = $triggering_element['#index'];

    $hosts = $form_state->get('hosts');
    unset($hosts[$index]);
    $form_state->set('hosts', $hosts);

    $form_state->setRebuild();
  }

  /**
   * Adds new entry to the list of hosts.
   *
   * @param array $form
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   */
  public function addHost(array $form, FormStateInterface $form_state) {
    // Store submitted host values.
    $this->copyHostValuesFromFormState($form_state);

    // Add a new host.
    $hosts = $form_state->get('hosts');
    $hosts[] = $this->getHostDefaultSettings();
    $form_state->set('hosts', $hosts);

    $form_state->setRebuild();
  }

  /**
   * Stores submitted host values.
   *
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   */
  public function copyHostValuesFromFormState(FormStateInterface $form_state) {
    // Filter out empty hosts.
    $hosts = array_filter($form_state->getValue('hosts'), function ($host) {
      return !empty($host['host']);
    });

    // Sort hosts by weight.
    uasort($hosts, ['Drupal\Component\Utility\SortArray', 'sortByWeightElement']);

    // Store only necessary values.
    $hosts = array_map(function ($host) {
      return [
        'scheme' => $host['scheme'],
        'host' => $host['host'],
        'port' => $host['port'],
        'authentication' => [
          'enabled' => $host['authentication']['enabled'],
          'user' => $host['authentication']['user'],
          'password' => $host['authentication']['password'],
        ],
      ];
    }, $hosts);

    $form_state->set('hosts', $hosts);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);

    // Store submitted values.
    $this->copyHostValuesFromFormState($form_state);

    // Reset host keys.
    $hosts = array_values($form_state->get('hosts'));
    $this->config->set('hosts', $hosts);
    $this->config->set('defer_indexing', $form_state->getValue('defer_indexing'));

    // Save submitted configuration values.
    $this->config->save();
  }

}
