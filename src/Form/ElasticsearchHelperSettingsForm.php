<?php

namespace Drupal\elasticsearch_helper\Form;

use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Form\SubformState;
use Drupal\elasticsearch_helper\ElasticsearchConnectionSettings;
use Elastic\Elasticsearch\Client;
use Elastic\Transport\Exception\NoNodeAvailableException;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class ElasticsearchHelperSettingsForm
 */
class ElasticsearchHelperSettingsForm extends ConfigFormBase {

  /**
   * The Elasticsearch client.
   *
   * @var \Elastic\Elasticsearch\Client
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
   * @param \Elastic\Elasticsearch\Client $elasticsearch_client
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
    $result = $this->client->cluster()->health()->asArray();

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
   * Checks connection to Elasticsearch server.
   *
   * @param array $element
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *
   * @return array
   */
  public function checkConnection(array $element, FormStateInterface $form_state) {
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
      catch (NoNodeAvailableException $e) {
        $this->messenger()->addError($this->t('Could not connect to Elasticsearch'));
      }
      catch (\Exception $e) {
        $this->messenger()->addError($e->getMessage());
      }
    }

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    // Register after build method that checks connection.
    $form['#after_build'][] = '::checkConnection';

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

    // Get available authentication methods.
    $auth_methods = $this->getAuthenticationMethodList();

    $form['authentication'] = [
      '#type' => 'details',
      '#title' => $this->t('Authentication'),
      '#open' => TRUE,
    ];

    $form['authentication']['method'] = [
      '#type' => 'select',
      '#title' => $this->t('Method'),
      '#description' => $this->t('Select the authentication method.'),
      '#options' => $auth_methods,
      '#default_value' => $this->config->get('authentication.method') ?: NULL,
      '#empty_option' => $this->t('- None -'),
    ];

    foreach ($auth_methods as $auth_method => $auth_label) {
      $auth_instance = $this->getAuthenticationMethodPlugin($auth_method);

      // Prepare the subform state.
      $auth_method_form = [];
      $auth_method_subform_state = SubformState::createForSubform($auth_method_form, $form, $form_state);
      $auth_method_form = $auth_instance->buildConfigurationForm([], $auth_method_subform_state);

      $form['authentication'][$auth_method] = [
        '#type' => 'fieldset',
        '#title' => $auth_label,
        '#states' => [
          'visible' => [
            ':input[name="method"]' => ['value' => $auth_method],
          ],
        ],
        '#tree' => TRUE,
        '#parents' => ['authentication', $auth_method],
      ] + $auth_method_form;
    }

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
  protected function prepareValuesFromFormState(FormStateInterface $form_state) {
    // Store only necessary values.
    $hosts = array_map(function ($host) {
      return [
        'host' => $host['host'] ?? '',
        'port' => $host['port'] ?? '',
      ];
    }, $form_state->getValue('hosts'));

    $form_state->set('hosts', $hosts);

    // Cast values to bool.
    $form_state->set(['ssl', 'skip_verification'], (bool) $form_state->get(['ssl', 'skip_verification']));
    $form_state->set('defer_indexing', (bool) $form_state->getValue('defer_indexing'));
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);

    $hosts = $form_state->getValue('hosts');

    // Make sure that hosts is always an array.
    if (!is_array($hosts)) {
      $hosts = [];
    }

    // Filter out empty hosts.
    $hosts = array_filter($hosts, function ($host) {
      return !empty($host['host']);
    });

    // Set the hosts values back.
    $form_state->setValue('hosts', $hosts);

    // Sort hosts by weight.
    uasort($hosts, ['Drupal\Component\Utility\SortArray', 'sortByWeightElement']);

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

    // Get authentication methods.
    $auth_method = $form_state->getValue('method');
    $authentication = [
      'method' => $auth_method,
      'configuration' => [],
    ];

    // Get authentication method configuration from plugin instance.
    if ($auth_method && $auth_instance = $this->getAuthenticationMethodPlugin($auth_method)) {
      // Get values from authentication method plugin form.
      $subform = &NestedArray::getValue($form, ['authentication', $auth_method]);
      $subform_state = SubformState::createForSubform($subform, $form, $form_state);
      $auth_instance->submitConfigurationForm($subform, $subform_state);

      $authentication['configuration'][$auth_method] = $auth_instance->getConfiguration();
    }

    // Save values.
    $this->config->set('scheme', $form_state->getValue('scheme'));
    $this->config->set('hosts', array_values($form_state->get('hosts')));
    $this->config->set('authentication', $authentication);
    $this->config->set('ssl', $form_state->getValue('ssl'));
    $this->config->set('defer_indexing', $form_state->getValue('defer_indexing'));

    // Save submitted configuration values.
    $this->config->save();
  }

  /**
   * Returns a list of authentication method titles, keyed by method ID.
   *
   * @return array
   */
  protected function getAuthenticationMethodList() {
    /** @var \Drupal\elasticsearch_helper\Plugin\ElasticsearchAuthPluginManager $elasticsearch_auth_manager */
    $elasticsearch_auth_manager = \Drupal::service('plugin.manager.elasticsearch_auth');

    // Get authentication methods.
    $auth_methods = $elasticsearch_auth_manager->getDefinitions();
    // Sort by weight.
    uasort($auth_methods, ['Drupal\Component\Utility\SortArray', 'sortByWeightElement']);

    // Fetch authentication method labels.
    return array_map(function($method) {
      return $method['label'];
    }, $auth_methods);
  }

  /**
   * Returns authentication method plugin instance.
   *
   * @param $plugin_id
   *
   * @return \Drupal\elasticsearch_helper\Plugin\ElasticsearchAuthInterface
   *
   * @throws \Drupal\Component\Plugin\Exception\PluginException
   */
  protected function getAuthenticationMethodPlugin($plugin_id) {
    /** @var \Drupal\elasticsearch_helper\Plugin\ElasticsearchAuthPluginManager $elasticsearch_auth_manager */
    $elasticsearch_auth_manager = \Drupal::service('plugin.manager.elasticsearch_auth');

    // Prepare authentication method plugin configuration.
    $configuration = $this->config->get('authentication.configuration.' . $plugin_id) ?: [];

    /** @var \Drupal\elasticsearch_helper\Plugin\ElasticsearchAuthInterface $instance */
    $instance = $elasticsearch_auth_manager->createInstance($plugin_id, $configuration);

    return $instance;
  }

}
