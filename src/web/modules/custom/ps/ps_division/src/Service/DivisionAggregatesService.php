<?php

declare(strict_types=1);

namespace Drupal\ps_division\Service;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\Logger\LoggerChannelInterface;

/**
 * Lightweight aggregates service for division totals (optional).
 *
 * Caches expensive parent-level total surface calculations.
 *
 * @see docs/specs/08-ps-division.md#42-divisionaggregatesservice
 */
final class DivisionAggregatesService {

  /**
   * Logger channel.
   *
   * @phpstan-ignore-next-line Logger prepared for future error logging
   */
  private readonly LoggerChannelInterface $logger;

  /**
   * Constructs a DivisionAggregatesService.
   */
  public function __construct(
    private readonly DivisionManagerInterface $divisionManager,
    private readonly CacheBackendInterface $cache,
    LoggerChannelFactoryInterface $loggerFactory,
  ) {
    $this->logger = $loggerFactory->get('ps_division');
  }

  /**
   * Gets cached total surface for parent entity ID.
   *
   * @param int $entityId
   *   Parent entity ID.
   *
   * @return float
   *   Total surface value.
   */
  public function getTotalSurface(int $entityId): float {
    $cid = "ps_division:agg:surface:{$entityId}";
    if ($cache = $this->cache->get($cid)) {
      if (is_float($cache->data)) {
        return $cache->data;
      }
    }

    $total = $this->divisionManager->calculateTotalSurface($entityId);
    $this->cache->set($cid, $total, CacheBackendInterface::CACHE_PERMANENT, [
      "ps_division_parent:{$entityId}",
    ]);

    return $total;
  }

  /**
   * Invalidates cached aggregates for a parent entity ID.
   *
   * @param int $entityId
   *   Parent entity ID.
   */
  public function invalidate(int $entityId): void {
    $cid = "ps_division:agg:surface:{$entityId}";
    $this->cache->delete($cid);
  }

}
