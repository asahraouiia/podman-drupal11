<?php

declare(strict_types=1);

namespace Drupal\ps_diagnostic\Service;

/**
 * Interface for diagnostic compare builder.
 *
 * Prepares diagnostic data for comparison display:
 * - Energy classes (type_code, label_code)
 * - Numeric values with units
 * - Completeness scores
 * - Validity periods.
 *
 * @see \Drupal\ps_diagnostic\Service\CompareBuilder
 * @see docs/specs/07-ps-diagnostic.md#44-compare-builder
 */
interface CompareBuilderInterface {

  /**
   * Builds comparison data structure for diagnostics.
   *
   * @param array<int, array<string, mixed>> $diagnostics
   *   Array of diagnostic field values.
   *
   * @return array
   *   Structured data for comparison with keys:
   *   - energy: array with label_code values
   *   - values: array with numeric values and units
   *   - completeness: highest score
   *   - validity: date ranges
   */
  public function buildCompareData(array $diagnostics): array;

}
