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
    $this->assertEquals([
      'foo.bar' => 'baz',
      'chip.shop' => 'snax',
    ], $config->getAll());
    $this->assertEquals([
      'FOO_BAR' => 'baz',
      'CHIP_SHOP' => 'snax',
    ], $config->getAll(TRUE));
    $this->assertEquals([], SkprConfig::create()->load(__DIR__ . '/fixtures/baz')->getAll());
  }

}
