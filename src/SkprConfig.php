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
   *   The config filename.
   *
   * @return $this
   */
  public function load(string $filename = self::DEFAULT_FILENAME): self {
    if (!is_readable($filename) || !is_file($filename)) {
      return $this;
    }
    if (empty($this->config)) {
      while (is_link($filename) || is_link(dirname($filename))) {
        clearstatcache(TRUE, $filename);
        $filename = dirname($filename) . '/' . readlink($filename);
      }
      $this->config = json_decode(file_get_contents($filename), TRUE);
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
  public function getAll(bool $environment_format = FALSE, string $filename = self::DEFAULT_FILENAME): array {
    $this->load($filename);
    if (!$environment_format) {
      return $this->config;
    }
    return array_combine(array_map(function (string $name) {
      return $this->convertToEnvVarName($name);
    }, array_keys($this->config)), $this->config);
  }

}
