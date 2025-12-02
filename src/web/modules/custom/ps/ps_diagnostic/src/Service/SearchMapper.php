<?php

declare(strict_types=1);

namespace Drupal\ps_diagnostic\Service;

/**
 * Diagnostic search mapper service.
 *
 * Extracts diagnostic data for search indexing.
 *
 * @see \Drupal\ps_diagnostic\Service\SearchMapperInterface
 * @see docs/specs/07-ps-diagnostic.md#43-search-mapper
 */
final class SearchMapper implements SearchMapperInterface {

  /**
   * {@inheritdoc}
   */

  /**
   * {@inheritdoc}
   */
  public function mapForSearch(array $diagnostics): array {
    $typeIds = [];
    $labelCodes = [];
    $valuesNumeric = [];
    $maxScore = 0;

    foreach ($diagnostics as $diagnostic) {
      if (array_key_exists('type_id', $diagnostic) && is_scalar($diagnostic['type_id']) && $diagnostic['type_id'] !== '') {
        $typeIds[] = (string) $diagnostic['type_id'];
      }

      if (array_key_exists('label_code', $diagnostic) && is_scalar($diagnostic['label_code']) && $diagnostic['label_code'] !== '') {
        $labelCodes[] = (string) $diagnostic['label_code'];
      }

      if (array_key_exists('value_numeric', $diagnostic) && is_numeric($diagnostic['value_numeric'])) {
        $valuesNumeric[] = (float) $diagnostic['value_numeric'];
      }

      // Track max completeness score if provided.
      if (array_key_exists('completeness_score', $diagnostic) && is_numeric($diagnostic['completeness_score'])) {
        $maxScore = max($maxScore, (int) $diagnostic['completeness_score']);
      }
    }

    return [
      'type_ids' => array_unique($typeIds),
      'label_codes' => array_unique($labelCodes),
      'values_numeric' => $valuesNumeric,
      'max_completeness_score' => $maxScore,
    ];
  }

}
