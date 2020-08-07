<?php

namespace Drupal\elasticsearch_helper;

/**
 * Class ElasticsearchHost
 */
class ElasticsearchHost {

  /**
   * Defines default Elasticsearch server port.
   */
  const PORT_DEFAULT = 9200;

  /**
   * @var string
   */
  protected $scheme;

  /**
   * @var string
   */
  protected $host;

  /**
   * @var string
   */
  protected $port;

  /**
   * @var int
   */
  protected $authEnabled;

  /**
   * @var string
   */
  protected $authUsername;

  /**
   * @var string
   */
  protected $authPassword;

  /**
   * ElasticsearchHost constructor.
   *
   * @param $scheme
   * @param $host
   * @param $port
   * @param $auth_enabled
   * @param $auth_username
   * @param $auth_password
   */
  public function __construct($scheme, $host, $port, $auth_enabled, $auth_username, $auth_password) {
    $this->scheme = $scheme;
    $this->host = $host;
    $this->port = $port;
    $this->authEnabled = $auth_enabled;
    $this->authUsername = $auth_username;
    $this->authPassword = $auth_password;
  }

  /**
   * Creates new host instance from configuration.
   *
   * @param array $values
   *
   * @return static
   */
  public static function createFromArray(array $values) {
    return new static(
      isset($values['scheme']) ? $values['scheme'] : NULL,
      isset($values['host']) ? $values['host'] : NULL,
      isset($values['port']) ? $values['port'] : NULL,
      isset($values['authentication']['enabled']) ? $values['authentication']['enabled'] : NULL,
      isset($values['authentication']['user']) ? $values['authentication']['user'] : NULL,
      isset($values['authentication']['password']) ? $values['authentication']['password'] : NULL
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
   * Returns hostname.
   *
   * @return string
   */
  public function getHost() {
    return $this->host;
  }

  /**
   * Returns port.
   *
   * @return string
   */
  public function getPort() {
    return $this->port;
  }

  /**
   * Returns 1 if authentication is enabled.
   *
   * @return int
   */
  public function isAuthEnabled() {
    return $this->authEnabled;
  }

  /**
   * Returns authentication username.
   *
   * @return string
   */
  public function getAuthUsername() {
    return $this->authUsername;
  }

  /**
   * Returns authentication username.
   *
   * @return string
   */
  public function getAuthPassword() {
    return $this->authPassword;
  }

}
