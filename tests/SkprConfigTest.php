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
    $config = SkprConfig::create()->load(__DIR__ . '/fixtures/config-link2.json');
    $this->assertEquals('wiz', $config->get('foo.bar'));
    $this->assertEquals('wiz', getenv('FOO_BAR'));
    $this->assertEquals(NULL, $config->get('does.not.exist'));
    $this->assertEquals('but does have a default', $config->get('does.not.exist', 'but does have a default'));
    $this->assertEquals('squirrel', $config->get('somewhat.secret'));
    $this->assertEquals('sssh', $config->get('super.secret'));
  }

  /**
   * @covers ::getAll
   */
  public function testGetAll() {
    $filename = __DIR__ . '/fixtures/config.json';
    $this->assertEquals([
      'foo.bar' => 'wiz',
      'chip.shop' => 'snax',
      'somewhat.secret' => 'squirrel',
      'super.secret' => 'sssh',
    ], SkprConfig::create()->getAll(FALSE, $filename));
    $this->assertEquals([
      'FOO_BAR' => 'wiz',
      'CHIP_SHOP' => 'snax',
      'SOMEWHAT_SECRET' => 'squirrel',
      'SUPER_SECRET' => 'sssh',
    ], SkprConfig::create()->getAll(TRUE, $filename));
    $this->assertEquals([], SkprConfig::create()->getAll(__DIR__ . '/fixtures/does_not_exist'));
  }

  /**
   * @covers ::ipRanges
   */
  public function testIpRanges() {
    $filename = __DIR__ . '/fixtures/ip-ranges.json';
    $skpr = SkprConfig::create();
    $ipRanges = $skpr->ipRanges($filename);
    $this->assertCount(119, $ipRanges);
    $this->assertContains("99.79.169.0/24", $ipRanges);
  }

  /**
   * @covers ::hostNames
   */
  public function testHostNames() {
    $filename = __DIR__ . '/fixtures/hostnames.json';
    $skpr = SkprConfig::create();
    $hostNames = $skpr->hostNames($filename);
    $this->assertCount(2, $hostNames);
    $this->assertContains("foo.bar", $hostNames);
  }

}
