<?php

namespace PNX\SkprConfig\Tests;

use PHPUnit\Framework\TestCase;
use PNX\SkprConfig\SkprConfig;

/**
 * @coversDefaultClass \PNX\SkprConfig\SkprConfig
 */
class SkprConfigTest extends TestCase {

  /**
   * @covers ::create()
   * @covers ::load()
   * @covers ::get()
   */
  public function testLoad() {
    $config = SkprConfig::create()->load(__DIR__ . '/fixtures');
    $this->assertEquals('baz', $config->get('foo.bar'));
    $this->assertEquals('baz', getenv('FOO_BAR'));
  }

}
