<?php

declare(strict_types=1);

namespace Drupal\ps_price\Entity;

use Drupal\Core\Config\Entity\ConfigEntityInterface;

/**
 * Interface for Price Rule configuration entities.
 *
 * Defines business rules for price display based on transaction type.
 * Each rule specifies default unit, period, currency, and VAT/charges flags.
 *
 * @see \Drupal\ps_price\Entity\PriceRule
 * @see docs/modules/ps_price.md#price-rules
 */
interface PriceRuleInterface extends ConfigEntityInterface {

  /**
   * Gets the transaction type code.
   *
   * @return string
   *   The transaction type code (e.g., 'OFFICE', 'COWORKING').
   */
  public function getTransactionType(): string;

  /**
   * Sets the transaction type code.
   *
   * @param string $transaction_type
   *   The transaction type code.
   *
   * @return $this
   */
  public function setTransactionType(string $transaction_type): static;

  /**
   * Gets the default unit code.
   *
   * @return string|null
   *   The unit code from price_unit dictionary or NULL.
   */
  public function getUnitCode(): ?string;

  /**
   * Sets the default unit code.
   *
   * @param string|null $unit_code
   *   The unit code.
   *
   * @return $this
   */
  public function setUnitCode(?string $unit_code): static;

  /**
   * Gets the default period code.
   *
   * @return string|null
   *   The period code from price_period dictionary or NULL.
   */
  public function getPeriodCode(): ?string;

  /**
   * Sets the default period code.
   *
   * @param string|null $period_code
   *   The period code.
   *
   * @return $this
   */
  public function setPeriodCode(?string $period_code): static;

  /**
   * Gets the default currency code.
   *
   * @return string|null
   *   The currency code from currency dictionary or NULL.
   */
  public function getCurrencyCode(): ?string;

  /**
   * Sets the default currency code.
   *
   * @param string|null $currency_code
   *   The currency code.
   *
   * @return $this
   */
  public function setCurrencyCode(?string $currency_code): static;

  /**
   * Checks if prices are VAT excluded (HT).
   *
   * @return bool
   *   TRUE if VAT excluded.
   */
  public function isVatExcluded(): bool;

  /**
   * Sets the VAT excluded flag.
   *
   * @param bool $is_vat_excluded
   *   TRUE to exclude VAT.
   *
   * @return $this
   */
  public function setVatExcluded(bool $is_vat_excluded): static;

  /**
   * Checks if charges are included.
   *
   * @return bool
   *   TRUE if charges included.
   */
  public function isChargesIncluded(): bool;

  /**
   * Sets the charges included flag.
   *
   * @param bool $is_charges_included
   *   TRUE to include charges.
   *
   * @return $this
   */
  public function setChargesIncluded(bool $is_charges_included): static;

  /**
   * Gets the rule weight for sorting.
   *
   * @return int
   *   The weight.
   */
  public function getWeight(): int;

  /**
   * Sets the rule weight.
   *
   * @param int $weight
   *   The weight.
   *
   * @return $this
   */
  public function setWeight(int $weight): static;

  /**
   * Checks if the rule is locked (cannot be deleted).
   *
   * @return bool
   *   TRUE if locked.
   */
  public function isLocked(): bool;

  /**
   * Sets the locked flag.
   *
   * @param bool $locked
   *   TRUE to lock.
   *
   * @return $this
   */
  public function setLocked(bool $locked): static;

}
