<?php

declare(strict_types=1);

namespace Drupal\ps_diagnostic\Service;

/**
 * Diagnostic compare builder service.
 *
 * Builds structured data for diagnostic comparison display.
 *
 * @see \Drupal\ps_diagnostic\Service\CompareBuilderInterface
 * @see docs/specs/07-ps-diagnostic.md#44-compare-builder
 */
final class CompareBuilder implements CompareBuilderInterface {

  /**
   * {@inheritdoc}
   */

  /**
   * {@inheritdoc}
   */
  public function buildCompareData(array $diagnostics): array {
    $energy = [];
    $values = [];
    $maxCompleteness = 0;
    $validity = [];

    foreach ($diagnostics as $diagnostic) {
      // Energy classes.
      if (array_key_exists('label_code', $diagnostic) && is_scalar($diagnostic['label_code']) && $diagnostic['label_code'] !== '') {
        $energy[] = (string) $diagnostic['label_code'];
      }

      // Numeric values.
      if (array_key_exists('value_numeric', $diagnostic) && is_numeric($diagnostic['value_numeric'])) {
        $values[] = [
          'value' => (float) $diagnostic['value_numeric'],
          'type_id' => (array_key_exists('type_id', $diagnostic) && is_scalar($diagnostic['type_id'])) ? (string) $diagnostic['type_id'] : '',
        ];
      }

      // Completeness score.
      if (array_key_exists('completeness_score', $diagnostic) && is_numeric($diagnostic['completeness_score'])) {
        $maxCompleteness = max($maxCompleteness, (int) $diagnostic['completeness_score']);
      }

      // Validity periods.
      if (array_key_exists('valid_from', $diagnostic) || array_key_exists('valid_to', $diagnostic)) {
        $validity[] = [
          'from' => (array_key_exists('valid_from', $diagnostic) && is_scalar($diagnostic['valid_from'])) ? (string) $diagnostic['valid_from'] : NULL,
          'to' => (array_key_exists('valid_to', $diagnostic) && is_scalar($diagnostic['valid_to'])) ? (string) $diagnostic['valid_to'] : NULL,
          'type_id' => (array_key_exists('type_id', $diagnostic) && is_scalar($diagnostic['type_id'])) ? (string) $diagnostic['type_id'] : '',
        ];
      }
    }

    return [
      'energy' => array_unique($energy),
      'values' => $values,
      'completeness' => $maxCompleteness,
      'validity' => $validity,
    ];
  }

}
