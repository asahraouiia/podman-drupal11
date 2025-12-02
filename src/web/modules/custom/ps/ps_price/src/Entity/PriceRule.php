<?php

declare(strict_types=1);

namespace Drupal\ps_price\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBase;
use Drupal\Core\Entity\Attribute\ConfigEntityType;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\ps_price\Entity\PriceRuleInterface;
use Drupal\ps_price\Form\PriceRuleDeleteForm;
use Drupal\ps_price\Form\PriceRuleForm;
use Drupal\ps_price\PriceRuleListBuilder;

/**
 * Defines the Price Rule configuration entity.
 *
 * Business rules for price display based on transaction type.
 * Each rule specifies default values for unit, period, currency,
 * and VAT/charges flags to guide price field widget behavior.
 *
 * @see docs/modules/ps_price.md#price-rules
 */
#[ConfigEntityType(
  id: 'ps_price_rule',
  label: new TranslatableMarkup('Price Rule'),
  label_collection: new TranslatableMarkup('Price Rules'),
  label_singular: new TranslatableMarkup('price rule'),
  label_plural: new TranslatableMarkup('price rules'),
  label_count: [
    'singular' => '@count price rule',
    'plural' => '@count price rules',
  ],
  handlers: [
    'list_builder' => PriceRuleListBuilder::class,
    'form' => [
      'add' => PriceRuleForm::class,
      'edit' => PriceRuleForm::class,
      'delete' => PriceRuleDeleteForm::class,
    ],
  ],
  config_prefix: 'rule',
  admin_permission: 'administer ps price',
  entity_keys: [
    'id' => 'id',
    'label' => 'label',
    'weight' => 'weight',
  ],
  config_export: [
    'id',
    'label',
    'transaction_type',
    'unit_code',
    'period_code',
    'currency_code',
    'is_vat_excluded',
    'is_charges_included',
    'weight',
    'locked',
  ],
  links: [
    'collection' => '/admin/ps/config/price/rules',
    'add-form' => '/admin/ps/config/price/rules/add',
    'edit-form' => '/admin/ps/config/price/rules/{ps_price_rule}/edit',
    'delete-form' => '/admin/ps/config/price/rules/{ps_price_rule}/delete',
  ],
)]
class PriceRule extends ConfigEntityBase implements PriceRuleInterface {

  /**
   * The price rule ID.
   */
  protected string $id = '';

  /**
   * The price rule label.
   */
  protected string $label = '';

  /**
   * The transaction type code.
   */
  protected string $transaction_type = '';

  /**
   * The default unit code.
   */
  protected ?string $unit_code = NULL;

  /**
   * The default period code.
   */
  protected ?string $period_code = NULL;

  /**
   * The default currency code.
   */
  protected ?string $currency_code = NULL;

  /**
   * Whether prices are VAT excluded (HT).
   */
  protected bool $is_vat_excluded = FALSE;

  /**
   * Whether charges are included.
   */
  protected bool $is_charges_included = FALSE;

  /**
   * The rule weight for sorting.
   */
  protected int $weight = 0;

  /**
   * Whether the rule is locked (cannot be deleted).
   */
  protected bool $locked = FALSE;

  /**
   * {@inheritdoc}
   */
  public function getTransactionType(): string {
    return $this->transaction_type;
  }

  /**
   * {@inheritdoc}
   */
  public function setTransactionType(string $transaction_type): static {
    $this->transaction_type = $transaction_type;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getUnitCode(): ?string {
    return $this->unit_code;
  }

  /**
   * {@inheritdoc}
   */
  public function setUnitCode(?string $unit_code): static {
    $this->unit_code = $unit_code;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getPeriodCode(): ?string {
    return $this->period_code;
  }

  /**
   * {@inheritdoc}
   */
  public function setPeriodCode(?string $period_code): static {
    $this->period_code = $period_code;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getCurrencyCode(): ?string {
    return $this->currency_code;
  }

  /**
   * {@inheritdoc}
   */
  public function setCurrencyCode(?string $currency_code): static {
    $this->currency_code = $currency_code;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function isVatExcluded(): bool {
    return $this->is_vat_excluded ?? FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function setVatExcluded(bool $is_vat_excluded): static {
    $this->is_vat_excluded = $is_vat_excluded;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function isChargesIncluded(): bool {
    return $this->is_charges_included ?? FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function setChargesIncluded(bool $is_charges_included): static {
    $this->is_charges_included = $is_charges_included;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getWeight(): int {
    return $this->weight ?? 0;
  }

  /**
   * {@inheritdoc}
   */
  public function setWeight(int $weight): static {
    $this->weight = $weight;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function isLocked(): bool {
    return $this->locked ?? FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function setLocked(bool $locked): static {
    $this->locked = $locked;
    return $this;
  }

}
