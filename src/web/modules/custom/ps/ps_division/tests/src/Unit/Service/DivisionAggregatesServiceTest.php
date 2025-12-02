<?php

declare(strict_types=1);

namespace Drupal\Tests\ps_division\Unit\Service;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\Logger\LoggerChannelInterface;
use Drupal\ps_division\Service\DivisionAggregatesService;
use Drupal\ps_division\Service\DivisionManagerInterface;
use Drupal\Tests\UnitTestCase;

/**
 * Unit tests for DivisionAggregatesService.
 *
 * @coversDefaultClass \Drupal\ps_division\Service\DivisionAggregatesService
 * @group ps_division
 */
final class DivisionAggregatesServiceTest extends UnitTestCase {

  /**
   * The mocked division manager.
   */
  private DivisionManagerInterface $divisionManager;

  /**
   * The mocked cache backend.
   */
  private CacheBackendInterface $cache;

  /**
   * The aggregates service under test.
   */
  private DivisionAggregatesService $aggregatesService;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->divisionManager = $this->createMock(DivisionManagerInterface::class);
    $this->cache = $this->createMock(CacheBackendInterface::class);
    $loggerFactory = $this->createMock(LoggerChannelFactoryInterface::class);

    $logger = $this->createMock(LoggerChannelInterface::class);
    $loggerFactory->method('get')->willReturn($logger);

    $this->aggregatesService = new DivisionAggregatesService(
      $this->divisionManager,
      $this->cache,
      $loggerFactory,
    );
  }

  /**
   * @covers ::getTotalSurface
   */
  public function testGetTotalSurfaceReturnsCachedValue(): void {
    $cacheData = (object) ['data' => 123.45];

    $this->cache
      ->expects($this->once())
      ->method('get')
      ->with('ps_division:agg:surface:1')
      ->willReturn($cacheData);

    $this->divisionManager
      ->expects($this->never())
      ->method('calculateTotalSurface');

    $result = $this->aggregatesService->getTotalSurface(1);
    $this->assertSame(123.45, $result);
  }

  /**
   * @covers ::getTotalSurface
   */
  public function testGetTotalSurfaceCalculatesAndCachesOnMiss(): void {
    $this->cache
      ->expects($this->once())
      ->method('get')
      ->with('ps_division:agg:surface:1')
      ->willReturn(FALSE);

    $this->divisionManager
      ->expects($this->once())
      ->method('calculateTotalSurface')
      ->with(1)
      ->willReturn(456.78);

    $this->cache
      ->expects($this->once())
      ->method('set')
      ->with(
        'ps_division:agg:surface:1',
        456.78,
        CacheBackendInterface::CACHE_PERMANENT,
        ['ps_division_list', 'ps_division_parent:1']
      );

    $result = $this->aggregatesService->getTotalSurface(1);
    $this->assertSame(456.78, $result);
  }

  /**
   * @covers ::invalidate
   */
  public function testInvalidateDeletesCacheEntry(): void {
    $this->cache
      ->expects($this->once())
      ->method('delete')
      ->with('ps_division:agg:surface:1');

    $this->aggregatesService->invalidate(1);
  }

}
