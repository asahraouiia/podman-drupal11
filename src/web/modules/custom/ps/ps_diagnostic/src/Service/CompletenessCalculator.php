<?php

declare(strict_types=1);

namespace Drupal\ps_diagnostic\Service;

use Drupal\Core\Config\ConfigFactoryInterface;

/**
 * Diagnostic completeness calculator service.
 *
 * Calculates weighted completeness score based on field presence.
 *
 * @see \Drupal\ps_diagnostic\Service\CompletenessCalculatorInterface
 * @see docs/specs/07-ps-diagnostic.md#42-completeness-calculator
 */
final class CompletenessCalculator implements CompletenessCalculatorInterface {

  /**
   * Field weights for completeness calculation.
   */
  private const FIELD_WEIGHTS = [
    'type_id' => 30,
    'value_numeric' => 25,
    'label_code' => 20,
    'valid_from' => 15,
    'valid_to' => 10,
  ];

  /**
   * Constructs a CompletenessCalculator.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $configFactory
   *   The config factory.
   */
  public function __construct(
    private readonly ConfigFactoryInterface $configFactory,
  ) {}

  /**
   * {@inheritdoc}
   */
  public function calculateScore(array $data): int {
    $config = $this->configFactory->get('ps_diagnostic.settings');

    // Check if completeness calculation is enabled.
    if (!$config->get('enable_completeness_score')) {
      return 0;
    }

    $totalWeight = array_sum(self::FIELD_WEIGHTS);
    $achievedWeight = 0;

    foreach (self::FIELD_WEIGHTS as $field => $weight) {
      if (!empty($data[$field])) {
        $achievedWeight += $weight;
      }
    }

    return (int) round(($achievedWeight / $totalWeight) * 100);
  }

  /**
   * {@inheritdoc}
   */
  public function getFieldWeights(): array {
    return self::FIELD_WEIGHTS;
  }

}
