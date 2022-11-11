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
   * Defines default scheme.
   */
  const SCHEME_DEFAULT = 'http';

  /**
   * @var string
   */
  protected $scheme;

  /**
   * @var array[]
   */
  protected $hosts = [];

  /**
   * @var null|string
   */
  protected $authMethod;

  /**
   * @var array
   */
  protected $authMethodConfiguration = [];

  /**
   * @var null|string
   */
  protected $sslCertificate = NULL;

  /**
   * @var null|bool
   */
  protected $skipSslVerification = NULL;

  /**
   * Connection settings constructor.
   *
   * @param array $hosts
   * @param string|null $scheme
   * @param array $authentication
   * @param array $ssl
   */
  public function __construct(array $hosts, $scheme, array $authentication, array $ssl) {
    $this->setHosts($hosts);
    $this->scheme = $scheme ?: static::SCHEME_DEFAULT;
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
      $values['hosts'] ?? [],
      $values['scheme'] ?? NULL,
      $values['authentication'] ?? [],
      $values['ssl'] ?? [],
    );
  }

  /**
   * Sets hosts.
   *
   * @param array $hosts
   *
   * @return void
   */
  public function setHosts(array $hosts) {
    $result = [];

    foreach ($hosts as $host) {
      $result[] = [
        'host' => $host['host'] ?? '',
        'port' => $host['port'] ?? '',
      ];
    }

    $this->hosts = $result;
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
   * Returns authentication method.
   *
   * @return null|string
   */
  public function getAuthMethod() {
    return $this->authMethod;
  }

  /**
   * Returns authentication method configuration.
   *
   * @param $auth_method
   *
   * @return array
   */
  public function getAuthMethodConfiguration($auth_method) {
    return $this->authMethodConfiguration[$auth_method] ?? [];
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

      $auth_method = $this->getAuthMethod();
      $configuration = $this->getAuthMethodConfiguration($auth_method);

      /** @var \Drupal\elasticsearch_helper\Plugin\ElasticsearchAuthInterface $instance */
      $instance = $elasticsearch_auth_manager->createInstance($auth_method, $configuration);

      return $instance;
    }

    return NULL;
  }

  /**
   * Returns a path to the SSL certificate.
   *
   * @return null|string
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
