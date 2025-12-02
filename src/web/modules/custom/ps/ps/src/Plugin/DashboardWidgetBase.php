<?php

declare(strict_types=1);

namespace Drupal\ps\Plugin;

use Drupal\Component\Plugin\PluginBase;

/**
 * Base class for DashboardWidget plugins.
 */
abstract class DashboardWidgetBase extends PluginBase implements DashboardWidgetInterface {

  /**
   * {@inheritdoc}
   */
  public function getSummary(): array {
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function getRefreshInterval(): int {
    return 0;
  }

}
