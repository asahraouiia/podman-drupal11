<?php

declare(strict_types=1);

namespace Drupal\ps\Service;

/**
 * Interface for HealthCheckManager service.
 */
interface HealthCheckManagerInterface {

  /**
   * Perform a complete system health check.
   *
   * @return array<string, mixed>
   *   Health check results with status and details.
   */
  public function performHealthCheck(): array;

  /**
   * Check database connectivity.
   *
   * @return bool
   *   TRUE if database is accessible.
   */
  public function checkDatabase(): bool;

  /**
   * Get system status summary.
   *
   * @return array<string, mixed>
   *   Status summary with key metrics.
   */
  public function getStatusSummary(): array;

}
