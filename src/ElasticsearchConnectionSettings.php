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
   * @var array
   */
  protected $basicAuth = [
    'user' => NULL,
    'password' => NULL,
  ];

  /**
   * @var array
   */
  protected $apiKey = [
    'id' => NULL,
    'api_key' => NULL,
  ];

  /**
   * @var array
   */
  protected $ssl = [
    'certificate' => NULL,
    'skip_verification' => NULL,
  ];

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
    $this->basicAuth['user'] = $authentication['basic_auth']['user'] ?? NULL;
    $this->basicAuth['password'] = $authentication['basic_auth']['password'] ?? NULL;
    $this->apiKey['id'] = $authentication['api_key']['id'] ?? NULL;
    $this->apiKey['api_key'] = $authentication['api_key']['api_key'] ?? NULL;
    $this->ssl['certificate'] = $ssl['certificate'] ?? NULL;
    $this->ssl['skip_verification'] = $ssl['skip_verification'] ?? NULL;
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
   * Returns basic auth user.
   *
   * @return string
   */
  public function getBasicAuthUser() {
    return $this->basicAuth['user'];
  }

  /**
   * Returns basic auth password.
   *
   * @return string
   */
  public function getBasicAuthPassword() {
    return $this->basicAuth['password'];
  }

  /**
   * Returns API key ID.
   *
   * @return string
   */
  public function getApiKeyId() {
    return $this->apiKey['id'];
  }

  /**
   * Returns the key from the API key information.
   *
   * @return string
   */
  public function getApiKey() {
    return $this->apiKey['api_key'];
  }

  /**
   * Returns a path to the SSL certificate.
   *
   * @return string
   */
  public function getSslCertificate() {
    return $this->ssl['certificate'];
  }

  /**
   * Returns TRUE if SSL certificate verification should be skipped.
   *
   * @return bool
   */
  public function skipSslVerification() {
    return (bool) $this->ssl['skip_verification'];
  }

}
