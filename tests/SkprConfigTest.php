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
   */
  public function testLoad() {
    SkprConfig::create()->load(__DIR__ . '/fixtures');
    $this->assertEquals('baz', getenv('FOO_BAR'));
  }

}
