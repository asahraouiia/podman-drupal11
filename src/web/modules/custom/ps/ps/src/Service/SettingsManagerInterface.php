<?php

declare(strict_types=1);

namespace Drupal\ps\Service;

/**
 * Interface for SettingsManager service.
 */
interface SettingsManagerInterface {

  /**
   * Get a setting value.
   *
   * @param string $key
   *   Setting key (dot notation supported: 'import.batch_size').
   * @param mixed $default
   *   Default value if not found.
   *
   * @return mixed
   *   Setting value.
   */
  public function get(string $key, mixed $default = NULL): mixed;

  /**
   * Set a setting value.
   *
   * @param string $key
   *   Setting key.
   * @param mixed $value
   *   Value to set.
   */
  public function set(string $key, mixed $value): void;

  /**
   * Check if performance monitoring is enabled.
   *
   * @return bool
   *   TRUE if enabled.
   */
  public function isPerformanceMonitoringEnabled(): bool;

  /**
   * Get slow request threshold (milliseconds).
   *
   * @return int
   *   Threshold in ms.
   */
  public function getSlowRequestThreshold(): int;

}
