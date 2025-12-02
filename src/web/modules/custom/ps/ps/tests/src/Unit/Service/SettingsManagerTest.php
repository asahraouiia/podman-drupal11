<?php

declare(strict_types=1);

namespace Drupal\Tests\ps\Unit\Service;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Config\ImmutableConfig;
use Drupal\ps\Service\SettingsManager;
use Drupal\Tests\UnitTestCase;

/**
 * Tests SettingsManager service.
 *
 * @coversDefaultClass \Drupal\ps\Service\SettingsManager
 * @group ps
 */
class SettingsManagerTest extends UnitTestCase {

  /**
   * The config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $configFactory;

  /**
   * The settings manager.
   *
   * @var \Drupal\ps\Service\SettingsManager
   */
  protected $settingsManager;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->configFactory = $this->createMock(ConfigFactoryInterface::class);
    $this->settingsManager = new SettingsManager($this->configFactory);
  }

  /**
   * Tests get method with simple key.
   *
   * @covers ::get
   */
  public function testGetSimple(): void {
    $config = $this->createMock(ImmutableConfig::class);
    $config->method('get')
      ->with('performance')
      ->willReturn(['enable_monitoring' => TRUE]);

    $this->configFactory->method('get')
      ->with('ps.settings')
      ->willReturn($config);

    $value = $this->settingsManager->get('performance');
    $this->assertSame(['enable_monitoring' => TRUE], $value);
  }

  /**
   * Tests get method with dot notation.
   *
   * @covers ::get
   */
  public function testGetDotNotation(): void {
    $config = $this->createMock(ImmutableConfig::class);
    $config->method('get')
      ->willReturnCallback(function ($key) {
        if ($key === 'performance') {
          return [
            'enable_monitoring' => TRUE,
            'slow_request_threshold' => 1500,
          ];
        }
        return NULL;
      });

    $this->configFactory->method('get')
      ->with('ps.settings')
      ->willReturn($config);

    $value = $this->settingsManager->get('performance.slow_request_threshold');
    $this->assertSame(1500, $value);
  }

  /**
   * Tests get method with default value.
   *
   * @covers ::get
   */
  public function testGetWithDefault(): void {
    $config = $this->createMock(ImmutableConfig::class);
    $config->method('get')
      ->willReturn(NULL);

    $this->configFactory->method('get')
      ->with('ps.settings')
      ->willReturn($config);

    $value = $this->settingsManager->get('nonexistent', 'default');
    $this->assertSame('default', $value);
  }

  /**
   * Tests isPerformanceMonitoringEnabled method.
   *
   * @covers ::isPerformanceMonitoringEnabled
   */
  public function testIsPerformanceMonitoringEnabled(): void {
    $config = $this->createMock(ImmutableConfig::class);
    $config->method('get')
      ->willReturnCallback(function ($key) {
        if ($key === 'performance') {
          return ['enable_monitoring' => TRUE];
        }
        return NULL;
      });

    $this->configFactory->method('get')
      ->with('ps.settings')
      ->willReturn($config);

    $this->assertTrue($this->settingsManager->isPerformanceMonitoringEnabled());
  }

  /**
   * Tests getSlowRequestThreshold method.
   *
   * @covers ::getSlowRequestThreshold
   */
  public function testGetSlowRequestThreshold(): void {
    $config = $this->createMock(ImmutableConfig::class);
    $config->method('get')
      ->willReturnCallback(function ($key) {
        if ($key === 'performance') {
          return ['slow_request_threshold' => 2000];
        }
        return NULL;
      });

    $this->configFactory->method('get')
      ->with('ps.settings')
      ->willReturn($config);

    $this->assertSame(2000, $this->settingsManager->getSlowRequestThreshold());
  }

}
