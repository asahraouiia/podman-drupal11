<?php

declare(strict_types=1);

namespace Drupal\ps_diagnostic\Service;

/**
 * Interface for diagnostic normalizer service.
 *
 * Provides validation and normalization of diagnostic data including:
 * - Type ID validation against PsDiagnosticType entities
 * - Numeric value conversion and validation
 * - Automatic class calculation from value
 * - Date format normalization
 * - Coherence checks (e.g., valid_from <= valid_to)
 * - Boolean flag normalization.
 *
 * @see \Drupal\ps_diagnostic\Service\DiagnosticNormalizer
 * @see docs/specs/07-ps-diagnostic.md#41-normalizer
 */
interface DiagnosticNormalizerInterface {

  /**
   * Normalizes diagnostic field data.
   *
   * Applies conversions, validations, and coherence checks.
   * Auto-calculates label_code from value_numeric if not provided.
   *
   * @param array $data
   *   Raw diagnostic data with keys: type_id, value_numeric, label_code,
   *   valid_from, valid_to, no_classification, non_applicable.
   *
   * @return array
   *   Normalized data.
   */
  public function normalize(array $data): array;

}
