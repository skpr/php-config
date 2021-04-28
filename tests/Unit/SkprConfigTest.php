<?php

namespace Skpr\Tests\Unit;

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
    $config = SkprConfig::create()->load(__DIR__ . '/../fixtures/config-link2.json');
    $this->assertEquals('wiz', $config->get('foo.bar'));
    $this->assertEquals(NULL, $config->get('does.not.exist'));
    $this->assertEquals('but does have a default', $config->get('does.not.exist', 'but does have a default'));
    $this->assertEquals('squirrel', $config->get('somewhat.secret'));
    $this->assertEquals('sssh', $config->get('super.secret'));
    $this->assertEquals('\value', $config->get('super.value'));
  }

  /**
   * @covers ::create
   * @covers ::load
   * @covers ::putAllEnvs
   */
  public function testPutAllEnvs() {
    $config = SkprConfig::create()->load(__DIR__ . '/../fixtures/config-link2.json');
    $config->putAllEnvs();
    $this->assertEquals('wiz', getenv('FOO_BAR'));
  }

  /**
   * @covers ::create
   * @covers ::load
   * @covers ::putEnvs
   */
  public function testPutEnvs() {
    // Clear env vars.
    putenv('FOO_BAR');
    putenv('SOMEWHAT_SECRET');
    putenv('CHIP_SHOP');
    putenv('SUPER_SECRET');
    putenv('SUPER_VALUE');

    $config = SkprConfig::create()->load(__DIR__ . '/../fixtures/config-link2.json');
    $config->putEnvs(['foo.bar', 'somewhat.secret']);

    $this->assertEquals('wiz', getenv('FOO_BAR'));
    $this->assertEquals('squirrel', getenv('SOMEWHAT_SECRET'));
    $this->assertFalse(getenv('CHIP_SHOP'));
    $this->assertFalse(getenv('SUPER_SECRET'));
    $this->assertFalse(getenv('SUPER_VALUE'));
  }

  /**
   * @covers ::getAll
   */
  public function testGetAll() {
    $filename = __DIR__ . '/../fixtures/config.json';
    $this->assertEquals([
      'foo.bar' => 'wiz',
      'chip.shop' => 'snax',
      'somewhat.secret' => 'squirrel',
      'super.secret' => 'sssh',
      'super.value' => '\value',
    ], SkprConfig::create()->getAll(FALSE, $filename));
    $this->assertEquals([
      'FOO_BAR' => 'wiz',
      'CHIP_SHOP' => 'snax',
      'SOMEWHAT_SECRET' => 'squirrel',
      'SUPER_SECRET' => 'sssh',
      'SUPER_VALUE' => '\value',
    ], SkprConfig::create()->getAll(TRUE, $filename));
    $this->assertEquals([], SkprConfig::create()->getAll(__DIR__ . '/../fixtures/does_not_exist'));
  }

  /**
   * @covers ::ipRanges
   */
  public function testIpRanges() {
    $filename = __DIR__ . '/../fixtures/ip-ranges.json';
    $skpr = SkprConfig::create();
    $ipRanges = $skpr->ipRanges($filename);
    $this->assertCount(119, $ipRanges);
    $this->assertContains("99.79.169.0/24", $ipRanges);
  }

  /**
   * @covers ::hostNames
   */
  public function testHostNames() {
    $filename = __DIR__ . '/../fixtures/hostnames.json';
    $skpr = SkprConfig::create();
    $hostNames = $skpr->hostNames($filename);
    $this->assertCount(2, $hostNames);
    $this->assertContains("foo.bar", $hostNames);
  }

}
