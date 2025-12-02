<?php

declare(strict_types=1);

namespace Drupal\ps_price\Service;

use Drupal\Core\Field\FieldItemInterface;

/**
 * Price normalizer service.
 *
 * Converts prices with different units to a common reference unit
 * (€/m²/year) for search comparison.
 *
 * @see docs/modules/ps_price.md#service-pricenormalizer
 */
final class PriceNormalizer {

  /**
   * Normalizes a price to reference unit (€/m²/year).
   *
   * @param \Drupal\ps_price\Plugin\Field\FieldType\PriceItem $item
   *   The price field item.
   * @param float $surfaceM2
   *   The surface area in m² (for conversion).
   *
   * @return float|null
   *   The normalized price or NULL if not applicable.
   */
  public function normalize(FieldItemInterface $item, float $surfaceM2 = 1.0): ?float {
    assert($item instanceof PriceItem);

    if ($item->is_on_request || $item->amount === NULL) {
      return NULL;
    }

    $amount = (float) $item->amount;
    $unit = $item->unit_code ?? '';
    $period = $item->period_code ?? 'year';

    // Apply period conversion.
    $amount = $this->convertPeriod($amount, $period);

    // Apply unit conversion.
    $amount = $this->convertUnit($amount, $unit, $surfaceM2);

    return $amount;
  }

  /**
   * Converts amount based on period.
   *
   * @param float $amount
   *   The amount.
   * @param string $period
   *   The period (year, month, quarter, week).
   *
   * @return float
   *   The amount converted to yearly.
   */
  private function convertPeriod(float $amount, string $period): float {
    return match ($period) {
      'month' => $amount * 12,
      'quarter' => $amount * 4,
      'week' => $amount * 52,
      default => $amount,
    };
  }

  /**
   * Converts amount based on unit.
   *
   * @param float $amount
   *   The amount.
   * @param string $unit
   *   The unit (e.g., /m²/an, /an).
   * @param float $surfaceM2
   *   The surface area in m².
   *
   * @return float
   *   The amount converted to per m².
   */
  private function convertUnit(float $amount, string $unit, float $surfaceM2): float {
    // If already per m², return as-is.
    if (str_contains($unit, 'm²') || str_contains($unit, 'm2')) {
      return $amount;
    }

    // If global price, divide by surface.
    if ($surfaceM2 > 0) {
      return $amount / $surfaceM2;
    }

    return $amount;
  }

}
