<?php

namespace PNX\SkprConfig;

/**
 * Utility for loading skipper config files into environment variables.
 */
class SkprConfig
{

  /**
   * Factory method.
   *
   * @return $this
   */
    public static function create()
    {
        return new static();
    }

    /**
   * Load skpr config into env vars.
   *
   * @param string $dir
   *   The config files directory.
   */
    public function load($dir = '/etc/skpr')
    {
        if (!is_readable($dir) || !is_dir($dir)) {
            return;
        }
        $files = array_diff(scandir($dir), ['..', '.']);
        array_map(
            function ($filename, $dir) {
                putenv($this->convertName($filename) . '=' . $this->readValue($dir . DIRECTORY_SEPARATOR . $filename));
            },
            array_filter($files, function ($filename) {
                return !is_dir($filename);
            }),
            [$dir]
        );
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
    protected function convertName($filename)
    {
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
    protected function readValue($filename)
    {
        return str_replace("\n", '', file_get_contents($filename));
    }
}
