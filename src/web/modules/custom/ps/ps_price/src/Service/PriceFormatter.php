<?php

declare(strict_types=1);

namespace Drupal\ps_price\Service;

use Drupal\Core\Field\FieldItemInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\ps_price\Plugin\Field\FieldType\PriceItem;

/**
 * Price formatter service.
 *
 * Provides locale-aware formatting of price field values with support
 * for business flags (on_request, from, VAT, charges) and price ranges.
 *
 * @see docs/modules/ps_price.md#service-priceformatter
 */
final class PriceFormatter implements PriceFormatterInterface {

  use StringTranslationTrait;

  /**
   * Constructs a PriceFormatter object.
   *
   * @param \Drupal\Core\Language\LanguageManagerInterface $languageManager
   *   The language manager service.
   */
  public function __construct(
    private readonly LanguageManagerInterface $languageManager,
  ) {}

  /**
   * {@inheritdoc}
   */
  public function format(FieldItemInterface $item, array $options = []): string {
    assert($item instanceof PriceItem);

    // Default options.
    $options += [
      'show_currency' => TRUE,
      'show_unit' => TRUE,
      'show_period' => TRUE,
      'show_flags' => TRUE,
    ];

    // Handle "on request" flag.
    if ($item->is_on_request) {
      return $this->t('On request')->render();
    }

    // Build price string.
    $parts = [];

    // Add "from" prefix if needed.
    if ($options['show_flags'] && $item->is_from) {
      $parts[] = $this->t('From')->render();
    }

    // Format amount or range.
    if ($item->amount_min !== NULL && $item->amount_max !== NULL && $item->amount_max > $item->amount_min) {
      $parts[] = $this->formatAmount($item->amount_min);
      $parts[] = '-';
      $parts[] = $this->formatAmount($item->amount_max);
    }
    elseif ($item->amount !== NULL) {
      $parts[] = $this->formatAmount($item->amount);
    }

    // Add currency.
    if ($options['show_currency'] && $item->currency_code) {
      $parts[] = $item->currency_code;
    }

    // Add unit.
    if ($options['show_unit'] && $item->unit_code) {
      $parts[] = $item->unit_code;
    }

    // Add period.
    if ($options['show_period'] && $item->period_code) {
      $parts[] = '/' . $item->period_code;
    }

    // Add flags.
    if ($options['show_flags']) {
      $flags = [];
      if ($item->is_vat_excluded) {
        $flags[] = $this->t('excl. VAT')->render();
      }
      if ($item->is_charges_included) {
        $flags[] = $this->t('charges incl.')->render();
      }
      if (!empty($flags)) {
        $parts[] = '(' . implode(', ', $flags) . ')';
      }
    }

    return implode(' ', $parts);
  }

  /**
   * {@inheritdoc}
   */
  public function formatShort(FieldItemInterface $item, array $options = []): string {
    assert($item instanceof PriceItem);

    // Default options.
    $options += [
      'show_currency' => TRUE,
    ];

    // Handle "on request" flag.
    if ($item->is_on_request) {
      return $this->t('On request')->render();
    }

    $parts = [];

    // Format amount with range support.
    if ($item->amount_min !== NULL && $item->amount_max !== NULL && $item->amount_max > $item->amount_min) {
      $parts[] = $this->formatAmount($item->amount_min) . '-' . $this->formatAmount($item->amount_max);
    }
    elseif ($item->amount !== NULL) {
      $parts[] = $this->formatAmount($item->amount);
    }

    // Add currency.
    if ($options['show_currency'] && $item->currency_code) {
      $parts[] = $item->currency_code;
    }

    return implode(' ', $parts);
  }

  /**
   * {@inheritdoc}
   */
  public function getNumericForSearch(FieldItemInterface $item): ?float {
    assert($item instanceof PriceItem);

    if ($item->is_on_request || $item->amount === NULL) {
      return NULL;
    }

    // For now, return the base amount.
    // In the future, this could apply unit conversion via PriceNormalizer.
    return (float) $item->amount;
  }

  /**
   * Formats a numeric amount according to current locale.
   *
   * @param float|null $amount
   *   The amount to format.
   *
   * @return string
   *   The formatted amount.
   */
  private function formatAmount(?float $amount): string {
    if ($amount === NULL) {
      return '0.00';
    }

    // Get current language for number formatting.
    $langcode = $this->languageManager->getCurrentLanguage()->getId();

    // Format number with thousands separator.
    // French: 1 250,00 | English: 1,250.00.
    if (str_starts_with($langcode, 'fr')) {
      return number_format($amount, 2, ',', ' ');
    }

    return number_format($amount, 2, '.', ',');
  }

}
