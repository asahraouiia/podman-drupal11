<?php

declare(strict_types=1);

namespace Drupal\ps_diagnostic\Service;

/**
 * Interface for diagnostic completeness calculator.
 *
 * Calculates completeness score (0-100) based on presence of key fields:
 * - type_code, status_code, label_code
 * - value_numeric, unit_code
 * - valid_from, valid_to
 * - reference.
 *
 * Score = sum(weights[field] for field present) / sum(weights) * 100
 *
 * @see \Drupal\ps_diagnostic\Service\CompletenessCalculator
 * @see docs/specs/07-ps-diagnostic.md#42-completeness-calculator
 */
interface CompletenessCalculatorInterface {

  /**
   * Calculates completeness score for diagnostic data.
   *
   * @param array $data
   *   Diagnostic data array.
   *
   * @return int
   *   Completeness score (0-100).
   */
  public function calculateScore(array $data): int;

  /**
   * Gets field weights for completeness calculation.
   *
   * @return array
   *   Associative array of field names to weights.
   */
  public function getFieldWeights(): array;

}
