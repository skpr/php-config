<?php

namespace Skpr;

/**
 * Utility for loading skipper config files.
 */
class SkprConfig {

  /**
   * The config filename.
   */
  const CONFIG_FILENAME = '/etc/skpr/data/config.json';

  /**
   * The IP ranges filename.
   */
  const IP_RANGES_FILENAME = '/etc/skpr/data/ip-ranges.json';

  /**
   * The hostnames filename.
   */
  const HOSTNAMES_FILE = '/etc/skpr/data/hostnames.json';

  /**
   * The list of file paths that need a cache clear.
   */
  const FILE_PATHS = [
    '/etc/skpr',
    '/etc/skpr/data',
    '/etc/skpr/data/..data',
    '/etc/skpr/data/..data/config.json',
    '/etc/skpr/data/config.json',
  ];

  /**
   * A map of config.
   *
   * @var string[]
   */
  protected $config = [];

  /**
   * The list of IP ranges.
   *
   * @var string[]
   */
  protected $ipRanges = [];

  /**
   * The list of hostnames.
   *
   * @var string[]
   */
  protected $hostNames = [];

  /**
   * Factory method.
   *
   * @return $this
   */
  public static function create() {
    return new static();
  }

  /**
   * Loads the skpr config.
   *
   * @param string $filename
   *   The config filename. Only used for testing.
   *
   * @return $this
   */
  public function load(string $filename = self::CONFIG_FILENAME): self {
    // We cache the config in memory.
    if (empty($this->config)) {
      $data = $this->loadFreshData($filename);
      if ($data === FALSE) {
        return $this;
      }
      $this->config = json_decode($data, TRUE);
      array_walk($this->config, function ($value, $key) {
        // Store as env vars.
        putenv($this->convertToEnvVarName($key) . '=' . $value);
      });
    }
    return $this;
  }

  /**
   * Gets the hostnames for the current environment.
   *
   * @param string $filename
   *   The filename used for testing.
   *
   * @return string[]
   *   The host names.
   */
  public function hostNames(string $filename = self::HOSTNAMES_FILE): array {
    return $this->readFile($filename, "hostNames");
  }

  /**
   * Gets a list of trusted IP Ranges used for reverse proxies.
   *
   * @param string $filename
   *   The filename used for testing.
   *
   * @return string[]
   *   The IP ranges.
   */
  public function ipRanges(string $filename = self::IP_RANGES_FILENAME): array {
    return $this->readFile($filename, "ipRanges");
  }

  /**
   * Reads the file and caches it in the given property.
   *
   * @param string $filename
   *   The filename.
   * @param string $property
   *   The property.
   *
   * @return string[]
   *   The data.
   */
  protected function readFile(string $filename, string $property): array {
    if (empty($this->{$property})) {
      $data = $this->loadFreshData($filename);
      if ($data === FALSE) {
        return [];
      }
      $this->{$property} = json_decode($data, TRUE);
    }
    return $this->{$property};
  }

  /**
   * Loads fresh data.
   *
   * Here is an adventure into how PHP caches stat data on the filesystem.
   * Kubernetes ConfigMaps structure mounted configuration as follows:
   * /etc/skpr/config.json ->
   * /etc/skpr/..data/config.json ->
   * /etc/skpr/..4984_21_04_13_51_28.237024315/config.json.
   *
   * The issue is here is when values are updated there is a short TTL of
   * time where PHP will keep looking at a non-existent timestamped
   * directory.
   *
   * After looking into opcache and apc it turns out core php has a cache
   * for this as well.
   *
   * These lines ensure that our Skipper configuration is always fresh and
   * readily available for the remaining config lookups by the
   * application.
   *
   * @param string $filename
   *   The file name.
   *
   * @return string|false
   *   The file data, or false if not found.
   */
  protected function loadFreshData(string $filename) {
    if (!is_readable($filename) || !is_file($filename)) {
      return FALSE;
    }
    $data = @file_get_contents(realpath($filename));
    // If the data is not found, symlinks may be outdated.
    if ($data === FALSE) {
      error_log("Failed loading Skpr config from: " . realpath($filename) . '. Clearing realpath caches.');
      foreach (self::FILE_PATHS as $path) {
        clearstatcache(TRUE, $path);
      }
      $data = @file_get_contents(realpath($filename));
      if ($data === FALSE) {
        // Nothing more we can do at this point.
        error_log("Failed to load skpr configuration from: " . realpath($filename));
        return $data;
      }
      error_log("Successfully loaded Skpr config from: " . realpath($filename));
    }
    return $data;
  }

  /**
   * Gets a config value.
   *
   * @param string $key
   *   The key.
   * @param mixed $fallback
   *   Default value if the config value doesn't exist.
   *
   * @return string|bool
   *   The config value or false if not found.
   */
  public function get(string $key, $fallback = FALSE) {
    return !empty($this->config[$key]) ? $this->config[$key] : $fallback;
  }

  /**
   * Converts an key to a valid env var key.
   *
   * @param string $key
   *   The key.
   *
   * @return string
   *   The env var key.
   */
  protected function convertToEnvVarName($key): string {
    return strtoupper(preg_replace("/[^A-Za-z0-9 ]/", '_', $key));
  }

  /**
   * Gets the list of variables.
   *
   * @param bool $environment_format
   *   TRUE to return variables in uppercase format e.g s3.bucket would be
   *   S3_BUCKET.
   * @param string $filename
   *   The config filename.
   *
   * @return array
   *   Values.
   */
  public function getAll(bool $environment_format = FALSE, string $filename = self::CONFIG_FILENAME): array {
    $this->load($filename);
    if (!$environment_format) {
      return $this->config;
    }
    return array_combine(array_map(function (string $name) {
      return $this->convertToEnvVarName($name);
    }, array_keys($this->config)), $this->config);
  }

}
