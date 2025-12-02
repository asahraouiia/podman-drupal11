<?php

declare(strict_types=1);

namespace Drupal\ps\Attribute;

use Drupal\Component\Plugin\Attribute\Plugin;
use Drupal\Core\StringTranslation\TranslatableMarkup;

/**
 * Defines a DashboardWidget attribute for plugin discovery.
 */
#[\Attribute(\Attribute::TARGET_CLASS)]
class DashboardWidget extends Plugin {

  /**
   * Constructs a DashboardWidget attribute.
   *
   * @param string $id
   *   The plugin ID.
   * @param \Drupal\Core\StringTranslation\TranslatableMarkup $label
   *   The human-readable label of the plugin.
   * @param \Drupal\Core\StringTranslation\TranslatableMarkup|null $description
   *   The description of the plugin.
   * @param string|null $category
   *   The category for grouping widgets.
   */
  public function __construct(
    public readonly string $id,
    public readonly TranslatableMarkup $label,
    public readonly ?TranslatableMarkup $description = NULL,
    public readonly ?string $category = NULL,
  ) {}

}
