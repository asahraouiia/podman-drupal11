<?php

declare(strict_types=1);

namespace Drupal\ps\Plugin;

use Drupal\Component\Plugin\PluginInspectionInterface;

/**
 * Interface for DashboardWidget plugins.
 */
interface DashboardWidgetInterface extends PluginInspectionInterface {

  /**
   * Build widget render array.
   *
   * @return array
   *   Render array.
   */
  public function build(): array;

  /**
   * Get widget summary data (JSON structure for KPIs).
   *
   * @return array<string, mixed>
   *   Summary data.
   */
  public function getSummary(): array;

  /**
   * Get refresh interval in seconds.
   *
   * @return int
   *   Interval (0 = no auto-refresh).
   */
  public function getRefreshInterval(): int;

}
