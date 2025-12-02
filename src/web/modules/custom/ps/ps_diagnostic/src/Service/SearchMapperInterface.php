<?php

declare(strict_types=1);

namespace Drupal\ps_diagnostic\Service;

/**
 * Interface for diagnostic search mapper.
 *
 * Extracts and maps diagnostic data for search indexing:
 * - type_code (string)
 * - label_code (string - energy class)
 * - value_numeric (float)
 * - completeness_score (int 0-100)
 *
 * @see \Drupal\ps_diagnostic\Service\SearchMapper
 * @see docs/specs/07-ps-diagnostic.md#43-search-mapper
 */
interface SearchMapperInterface {

  /**
   * Maps diagnostic data for search indexing.
   *
   * @param array<int, array<string, mixed>> $diagnostics
   *   Array of diagnostic field values.
   *
   * @return array
   *   Mapped data for search with keys:
   *   - type_codes: array of type codes
   *   - label_codes: array of label codes (energy classes)
   *   - values_numeric: array of numeric values
   *   - max_completeness_score: highest score among diagnostics
   */
  public function mapForSearch(array $diagnostics): array;

}
