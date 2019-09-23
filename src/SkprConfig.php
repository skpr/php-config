<?php

namespace Skpr;

/**
 * Utility for loading skipper config files into environment variables.
 */
class SkprConfig {

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
   * @param string $base_dir
   *   The config files base directory.
   *
   * @return $this
   */
  public function load($base_dir = '/etc/skpr') {
    if (!is_readable($base_dir) || !is_dir($base_dir)) {
      return $this;
    }
    if (empty($this->config)) {
      // Check default config, followed by overridden config, then the same
      // from secrets.
      $dirs = [
        'config/default',
        'config/override',
        'secret/default',
        'secret/override',
      ];
      foreach ($dirs as $dir) {
        $dir = $base_dir . '/' . $dir;
        if (!is_readable($dir) || !is_dir($dir)) {
          throw new InvalidConfigDirectoryException("$dir is not a valid config directory");
        }
        // Here is an adventure into how PHP caches stat data on the filesystem.
        // Kubernetes ConfigMaps structure mounted configuration as follows:
        // /etc/skpr/var.foo -> /etc/skpr/..data/var.foo ->
        // /etc/skpr/..4984_21_04_13_51_28.237024315/var.foo
        // The issue is here is when values are updated there is a short TTL of
        // time where PHP will keep looking at a non existant time-stamped
        // directory. After looking into opcache and apc it turns out core php
        // has a cache for this as well. These lines ensure that our Skipper
        // configuration is always fresh and readily available for the remaining
        // config lookups by the application.
        foreach (realpath_cache_get() as $path => $cache) {
          if (strpos($path, $dir) === 0) {
            clearstatcache(TRUE, $path);
          }
        }
        $files = array_diff(glob($dir . '/*'), ['..', '.']);
        array_map(
          function ($filename) {
            $key = basename($filename);
            $value = $this->readValue($filename);
            // Update the static cache.
            $this->config[$key] = $value;
            // Store as env vars.
            putenv($this->convertToEnvVarName($key) . '=' . $value);
          },
          array_filter($files, function ($filename) {
            return !is_dir($filename);
          })
        );
      }
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
  public function get($key, $fallback = FALSE) {
    return !empty($this->config[$key]) ? $this->config[$key] : $fallback;
  }

  /**
   * Converts a filename to a valid env var key.
   *
   * @param string $filename
   *   The file name.
   *
   * @return string
   *   The env var key.
   */
  protected function convertToEnvVarName($filename) {
    return strtoupper(preg_replace("/[^A-Za-z0-9 ]/", '_', $filename));
  }

  /**
   * Reads the value from the file.
   *
   * @param string $filename
   *   The file name.
   *
   * @return string
   *   The value.
   */
  protected function readValue($filename) {
    return str_replace("\n", '', file_get_contents(realpath($filename)));
  }

  /**
   * Gets the list of variables.
   *
   * @param bool $environment_format
   *   TRUE to return variables in uppercase format e.g s3.bucket would be
   *   S3_BUCKET.
   * @param string $base_dir
   *   The base directory to look for config.
   *
   * @return array
   *   Values.
   */
  public function getAll(bool $environment_format = FALSE, string $base_dir = '/etc/skpr'): array {
    $this->load($base_dir);
    if (!$environment_format) {
      return $this->config;
    }
    return array_combine(array_map(function (string $name) {
      return $this->convertToEnvVarName($name);
    }, array_keys($this->config)), $this->config);
  }

}
