<?php

namespace Skpr\Tests;

use PHPUnit\Framework\TestCase;
use Skpr\SkprConfig;

/**
 * @coversDefaultClass \Skpr\SkprConfig
 */
class SkprConfigTest extends TestCase {

  /**
   * @covers ::create()
   * @covers ::load()
   * @covers ::get()
   */
  public function testLoad() {
    $base_dir = __DIR__ . '/fixtures';
    $config = SkprConfig::create()->load($base_dir);
    $this->assertEquals('wiz', $config->get('foo.bar'));
    $this->assertEquals('wiz', getenv('FOO_BAR'));
    $this->assertEquals(NULL, $config->get('does.not.exist'));
    $this->assertEquals('but does have a default', $config->get('does.not.exist', 'but does have a default'));
    $this->assertEquals('squirrel', $config->get('somewhat.secret'));
    $this->assertEquals('sssh', $config->get('super.secret'));
    $this->assertEquals([
      'foo.bar' => 'wiz',
      'chip.shop' => 'snax',
      'somewhat.secret' => 'squirrel',
      'super.secret' => 'sssh',
    ], $config->getAll(FALSE, $base_dir));
    $this->assertEquals([
      'FOO_BAR' => 'wiz',
      'CHIP_SHOP' => 'snax',
      'SOMEWHAT_SECRET' => 'squirrel',
      'SUPER_SECRET' => 'sssh',
    ], $config->getAll(TRUE, $base_dir));
    $this->assertEquals([], SkprConfig::create()->load(__DIR__ . '/fixtures/baz')->getAll());
  }

}
