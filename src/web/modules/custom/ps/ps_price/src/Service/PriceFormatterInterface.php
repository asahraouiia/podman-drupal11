<?php

declare(strict_types=1);

namespace Drupal\ps_price\Service;

use Drupal\Core\Field\FieldItemInterface;

/**
 * Interface for price formatting service.
 *
 * @see docs/modules/ps_price.md#service-priceformatter
 */
interface PriceFormatterInterface {

  /**
   * Formats a price item with full details.
   *
   * @param \Drupal\ps_price\Plugin\Field\FieldType\PriceItem $item
   *   The price field item.
   * @param array<string, mixed> $options
   *   Formatting options (show_currency, show_unit, show_period, show_flags).
   *
   * @return string
   *   The formatted price string.
   */
  public function format(FieldItemInterface $item, array $options = []): string;

  /**
   * Formats a price item in short format (amount + currency only).
   *
   * @param \Drupal\ps_price\Plugin\Field\FieldType\PriceItem $item
   *   The price field item.
   * @param array<string, mixed> $options
   *   Formatting options (show_currency).
   *
   * @return string
   *   The formatted price string.
   */
  public function formatShort(FieldItemInterface $item, array $options = []): string;

  /**
   * Gets normalized numeric value for search indexing.
   *
   * Converts price to reference unit (€/m²/year) for comparison.
   *
   * @param \Drupal\ps_price\Plugin\Field\FieldType\PriceItem $item
   *   The price field item.
   *
   * @return float|null
   *   The normalized price value or NULL if not applicable.
   */
  public function getNumericForSearch(FieldItemInterface $item): ?float;

}
