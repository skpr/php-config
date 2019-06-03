<?php

namespace PNX\SkprConfig;

/**
 * Utility for loading skipper config files into environment variables.
 */
class SkprConfig {

  /**
   * A map of config.
   *
   * @var string[]
   */
  protected $config;

  /**
   * Factory method.
   *
   * @return $this
   */
  public static function create() {
    return new static();
  }

  /**
   * Load skpr config into env vars.
   *
   * @param string $dir
   *   The config files directory.
   *
   * @return $this
   */
  public function load($dir = '/etc/skpr') {
    if (!is_readable($dir) || !is_dir($dir)) {
      return $this;
    }
    // Here is an adventure into how PHP caches stat data on the filesystem.
    // Kubernetes ConfigMaps structure mounted configuration as follows:
    // /etc/skpr/var.foo ->
    // /etc/skpr/..data/var.foo ->
    // /etc/skpr/..4984_21_04_13_51_28.237024315/var.foo
    //
    // The issue is here is when values are updated there is a short TTL of time
    // where PHP will keep looking at a non existant timestamped directory.
    // After looking into opcache and apc it turns out core php has a cache for
    // this as well. These lines ensure that our Skipper configuration is always
    // fresh and readily available for the remaining config lookups by the
    // application.
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
   * @return string
   *   The value.
   */
  public function get($key, $fallback = NULL) {
    return $this->config[$key] ?? $fallback;
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
    return strtoupper(str_replace('.', '_', $filename));
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

}
