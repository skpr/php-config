<?php

namespace Skpr;

/**
 * Utility for loading skipper config files.
 */
class SkprConfig {

  /**
   * The default filename.
   */
  const DEFAULT_FILENAME = '/etc/skpr/data/config.json';

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
   * Factory method.
   *
   * @return $this
   */
  public static function create() {
    return new static();
  }

  /**
   * Load skpr config.
   *
   * @param string $filename
   *   The config filename. Only used for testing.
   *
   * @return $this
   */
  public function load(string $filename = self::DEFAULT_FILENAME): self {
    if (!is_readable($filename) || !is_file($filename)) {
      return $this;
    }
    // We cache the config in memory.
    if (empty($this->config)) {
      $data = @file_get_contents(realpath($filename));
      // If the data is not found, symlinks may be outdated.
      if ($data === FALSE) {
        // Here is an adventure into how PHP caches stat data on the filesystem.
        // Kubernetes ConfigMaps structure mounted configuration as follows:
        // /etc/skpr/config.json ->
        // /etc/skpr/..data/config.json ->
        // /etc/skpr/..4984_21_04_13_51_28.237024315/config.json
        //
        // The issue is here is when values are updated there is a short TTL of
        // time where PHP will keep looking at a non-existent timestamped
        // directory.
        //
        // After looking into opcache and apc it turns out core php has a cache
        // for this as well.
        //
        // These lines ensure that our Skipper configuration is always fresh and
        // readily available for the remaining config lookups by the
        // application.
        error_log("Failed loading Skpr config from: " . realpath($filename) . ' Clearing realpath caches.');
        foreach (self::FILE_PATHS as $path) {
          clearstatcache(TRUE, $path);
        }
        $data = @file_get_contents(realpath($filename));
        if ($data === FALSE) {
          // Nothing more we can do at this point.
          error_log("Failed to load skpr configuration from: " . realpath($filename));
          return $this;
        }
        error_log("Successfully loaded Skpr config from: " . realpath($filename));
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
  public function getAll(
    bool $environment_format = FALSE,
    string $filename = self::DEFAULT_FILENAME
  ): array {
    $this->load($filename);
    if (!$environment_format) {
      return $this->config;
    }
    return array_combine(array_map(function (string $name) {
      return $this->convertToEnvVarName($name);
    }, array_keys($this->config)), $this->config);
  }

}
