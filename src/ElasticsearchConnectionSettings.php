<?php

namespace Drupal\elasticsearch_helper;

/**
 * Elasticsearch Helper settings class.
 */
class ElasticsearchConnectionSettings {

  /**
   * Defines default Elasticsearch server port.
   */
  const PORT_DEFAULT = 9200;

  /**
   * @var string
   */
  protected $scheme = 'https';

  /**
   * @var array[]
   */
  protected $hosts = [];

  /**
   * @var null|string
   */
  protected $authMethod = [];

  /**
   * @var array
   */
  protected $authMethodConfiguration = [];

  /**
   * @var null
   */
  protected $sslCertificate = NULL;

  /**
   * @var null|bool
   */
  protected $skipSslVerification = NULL;

  /**
   * Connection settings constructor.
   *
   * @param $scheme
   * @param array $hosts
   * @param array $authentication
   * @param array $ssl
   */
  public function __construct($scheme, array $hosts, array $authentication, array $ssl) {
    $this->scheme = $scheme;
    $this->hosts = $hosts;
    $this->authMethod = $authentication['method'] ?? NULL;
    $this->authMethodConfiguration = $authentication['configuration'] ?? [];
    $this->sslCertificate = $ssl['certificate'] ?? NULL;
    $this->skipSslVerification = $ssl['skip_verification'] ?? NULL;
  }

  /**
   * Creates new connection settings instance from an array.
   *
   * @param array $values
   *
   * @return static
   */
  public static function createFromArray(array $values) {
    return new static(
      $values['scheme'] ?? NULL,
      $values['hosts'] ?? [],
      $values['authentication'] ?? [],
      $values['ssl'] ?? [],
    );
  }

  /**
   * Returns scheme.
   *
   * @return string
   */
  public function getScheme() {
    return $this->scheme;
  }

  /**
   * Returns hosts.
   *
   * @return array
   */
  public function getHosts() {
    return $this->hosts;
  }

  /**
   * Returns a list of formatted host URLs (scheme, hostname and port).
   *
   * @return array
   */
  public function getFormattedHosts() {
    $result = [];

    foreach ($this->hosts as $host) {
      if (!empty($host['host'])) {
        $port = $host['port'] ?: self::PORT_DEFAULT;
        $result[] = sprintf('%s://%s:%d', $this->getScheme(), $host['host'], $port);
      }
    }

    return $result;
  }

  /**
   * Returns a list of authentication method plugin instances.
   *
   * @return \Drupal\elasticsearch_helper\Plugin\ElasticsearchAuthInterface|null
   *
   * @throws \Drupal\Component\Plugin\Exception\PluginException
   */
  public function getAuthMethodInstance() {
    if ($this->authMethod) {
      /** @var \Drupal\elasticsearch_helper\Plugin\ElasticsearchAuthPluginManager $elasticsearch_auth_manager */
      $elasticsearch_auth_manager = \Drupal::service('plugin.manager.elasticsearch_auth');

      // Prepare authentication method plugin configuration.
      $configuration = $this->authMethodConfiguration[$this->authMethod] ?? [];

      /** @var \Drupal\elasticsearch_helper\Plugin\ElasticsearchAuthInterface $instance */
      $result = $elasticsearch_auth_manager->createInstance($this->authMethod, $configuration);

      return $result;
    }

    return NULL;
  }

  /**
   * Returns a path to the SSL certificate.
   *
   * @return string
   */
  public function getSslCertificate() {
    return $this->sslCertificate;
  }

  /**
   * Returns TRUE if SSL certificate verification should be skipped.
   *
   * @return bool
   */
  public function skipSslVerification() {
    return (bool) $this->skipSslVerification;
  }

}
