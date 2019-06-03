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
    $this->assertEquals(NULL, $config->get('does.not.exist'));
    $this->assertEquals('but does have a default', $config->get('does.not.exist', 'but does have a default'));
  }

}
