<?php

declare(strict_types=1);

namespace Drupal\ps_diagnostic\Service;

/**
 * Interface for diagnostic class calculation service.
 *
 * Calculates energy class from numeric value using PsDiagnosticType configuration.
 *
 * @see docs/specs/07-ps-diagnostic.md
 */
interface DiagnosticClassCalculatorInterface {

  /**
   * Calculates the energy class for a diagnostic value.
   *
   * @param string $typeId
   *   The diagnostic type ID (dpe, ges, etc.).
   * @param float $value
   *   The numeric value.
   *
   * @return string|null
   *   The calculated class code (A-G) or NULL if invalid.
   */
  public function calculateClass(string $typeId, float $value): ?string;

  /**
   * Gets display info for a diagnostic.
   *
   * Returns rendered information including class, color, unit, and special states.
   *
   * @param array{type_id?: string, value_numeric?: float, label_code?: string, no_classification?: bool, non_applicable?: bool} $diagnosticData
   *   Diagnostic field data.
   *
   * @return array{class: string|null, color: string|null, unit: string|null, display_text: string, is_special: bool}
   *   Display information.
   */
  public function getDisplayInfo(array $diagnosticData): array;

}
