<?php

declare(strict_types=1);

namespace Drupal\ps_diagnostic\Hook;

use Drupal\Core\Hook\Attribute\Hook;

/**
 * Hook implementations for ps_diagnostic module.
 */
final class PsDiagnosticHooks {

  /**
   * Implements hook_theme().
   */
  #[Hook('theme')]
  public function theme(array $existing, string $type, string $theme, string $path): array {
    return [
      'ps_diagnostic_display' => [
        'variables' => [
          'display_info' => [],
          'value' => NULL,
          'valid_from' => NULL,
          'valid_to' => NULL,
          'layout' => 'horizontal',
          'diagnostic_type' => NULL,
          'all_classes' => [],
          'is_dimmed' => FALSE,
          'dim_opacity' => 30,
        ],
        'template' => 'ps-diagnostic-display',
      ],
    ];
  }

}
