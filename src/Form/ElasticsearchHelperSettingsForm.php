<?php

namespace Drupal\elasticsearch_helper\Form;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\elasticsearch_helper\ElasticsearchConnectionSettings;
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
      'host' => NULL,
      'port' => NULL,
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
    if (!$form_state->isProcessingInput()) {
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
    }

    // Get Elasticsearch connection settings.
    $connection = ElasticsearchConnectionSettings::createFromArray($this->config->getRawData());

    $header = [
      '',
      $this->t('Host'),
      $this->t('Port'),
      $this->t('Weight'),
      $this->t('Remove'),
    ];

    $form['scheme'] = [
      '#type' => 'select',
      '#title' => $this->t('Scheme'),
      '#options' => [
        'https' => 'https',
        'http' => 'http',
      ],
      '#default_value' => $connection->getScheme(),
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
      $form['hosts'][$index]['#attributes'] = ['class' => ['draggable'], 'id' => 'row-' . $index];

      $form['hosts'][$index]['handle'] = [];

      $form['hosts'][$index]['host'] = [
        '#type' => 'textfield',
        '#default_value' => $host['host'],
        '#size' => 32,
      ];

      $form['hosts'][$index]['port'] = [
        '#type' => 'textfield',
        '#maxlength' => 4,
        '#placeholder' => ElasticsearchConnectionSettings::PORT_DEFAULT,
        '#size' => 4,
        '#default_value' => $host['port'],
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

    $form['authentication'] = [
      '#type' => 'details',
      '#title' => $this->t('Authentication'),
      '#open' => TRUE,
    ];

    $form['authentication']['basic_auth'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Basic auth'),
      '#description' => $this->t('Credentials for authentication with a username and password.'),
    ];

    $form['authentication']['basic_auth']['user'] = [
      '#type' => 'textfield',
      '#title' => $this->t('User'),
      '#default_value' => $connection->getBasicAuthUser(),
      '#size' => 32,
      '#parents' => ['authentication', 'basic_auth', 'user'],
    ];

    $form['authentication']['basic_auth']['password'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Password'),
      '#default_value' => $connection->getBasicAuthPassword(),
      '#size' => 32,
      '#parents' => ['authentication', 'basic_auth', 'password'],
    ];

    $form['authentication']['api_key'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('API key'),
      '#description' => $this->t('Credentials for authentication with an API key.'),
    ];

    $form['authentication']['api_key']['id'] = [
      '#type' => 'textfield',
      '#title' => $this->t('ID'),
      '#default_value' => $connection->getApiKeyId(),
      '#size' => 32,
      '#parents' => ['authentication', 'api_key', 'id'],
    ];

    $form['authentication']['api_key']['api_key'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Key'),
      '#default_value' => $connection->getApiKey(),
      '#size' => 32,
      '#parents' => ['authentication', 'api_key', 'api_key'],
    ];

    $form['ssl'] = [
      '#type' => 'details',
      '#title' => $this->t('SSL'),
      '#open' => TRUE,
    ];

    $form['ssl']['certificate'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Certificate'),
      '#description' => $this->t('The name of a file containing a PEM formatted certificate.'),
      '#default_value' => $connection->getSslCertificate(),
      '#size' => 32,
      '#parents' => ['ssl', 'certificate'],
    ];

    $form['ssl']['skip_verification'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Skip SSL certificate verification'),
      '#default_value' => (int) $connection->skipSslVerification(),
      '#parents' => ['ssl', 'skip_verification'],
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
    $this->prepareValuesFromFormState($form_state);

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
    $this->prepareValuesFromFormState($form_state);

    // Add a new host.
    $hosts = $form_state->get('hosts');
    $hosts[] = $this->getHostDefaultSettings();
    $form_state->set('hosts', $hosts);

    $form_state->setRebuild();
  }

  /**
   * Prepares submitted values.
   *
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   */
  public function prepareValuesFromFormState(FormStateInterface $form_state) {
    // Filter out empty hosts.
    $hosts = array_filter($form_state->getValue('hosts'), function ($host) {
      return !empty($host['host']);
    });

    // Sort hosts by weight.
    uasort($hosts, ['Drupal\Component\Utility\SortArray', 'sortByWeightElement']);

    // Store only necessary values.
    $hosts = array_map(function ($host) {
      return [
        'host' => $host['host'],
        'port' => $host['port'],
      ];
    }, $hosts);

    $form_state->set('hosts', $hosts);

    // Cast SSL verification setting to bool.
    $form_state->set(['ssl', 'skip_verification'], (bool) $form_state->get(['ssl', 'skip_verification']));
  }

  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);

    foreach ($form_state->getValue('hosts') as $index => $host) {
      if (isset($host['host'])) {
        $url = parse_url($host['host']);

        if (isset($url['scheme'])) {
          $form_state->setErrorByName(sprintf('hosts][%s][host', $index), $this->t('Host name should not include an URI scheme (https or http).'));
        }
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);

    // Prepare submitted values.
    $this->prepareValuesFromFormState($form_state);

    $this->config->set('scheme', $form_state->getValue('scheme'));
    $hosts = array_values($form_state->get('hosts'));
    $this->config->set('hosts', $hosts);
    $this->config->set('authentication', $form_state->getValue('authentication'));
    $this->config->set('ssl', $form_state->getValue('ssl'));
    $this->config->set('defer_indexing', (bool) $form_state->getValue('defer_indexing'));

    // Save submitted configuration values.
    $this->config->save();
  }

}
