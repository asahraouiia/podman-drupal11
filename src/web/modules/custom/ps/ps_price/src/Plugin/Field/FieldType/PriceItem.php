<?php

declare(strict_types=1);

namespace Drupal\ps_price\Plugin\Field\FieldType;

use Drupal\Core\Field\Attribute\FieldType;
use Drupal\Core\Field\FieldItemBase;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\TypedData\DataDefinition;

/**
 * Defines the 'ps_price' field type.
 *
 * Stores structured price data with amount, currency, unit, period and flags.
 * Supports price ranges and business flags (on_request, from, VAT, charges).
 *
 * @see docs/modules/ps_price.md
 */
#[FieldType(
  id: "ps_price",
  label: new TranslatableMarkup("Price"),
  description: new TranslatableMarkup("Stores structured price with currency, unit, period, and business flags."),
  category: "PropertySearch",
  default_widget: "ps_price_default",
  default_formatter: "ps_price_full",
)]
class PriceItem extends FieldItemBase {

  /**
   * {@inheritdoc}
   */
  public static function propertyDefinitions(FieldStorageDefinitionInterface $field_definition): array {
    $properties['amount'] = DataDefinition::create('float')
      ->setLabel(new TranslatableMarkup('Amount'))
      ->setDescription(new TranslatableMarkup('Main price amount'));

    $properties['currency_code'] = DataDefinition::create('string')
      ->setLabel(new TranslatableMarkup('Currency code'))
      ->setDescription(new TranslatableMarkup('ISO currency code (e.g., EUR, USD)'));

    $properties['value_type_code'] = DataDefinition::create('string')
      ->setLabel(new TranslatableMarkup('Value type code'))
      ->setDescription(new TranslatableMarkup('Value type code from dictionary (MIN, MAX)'));

    $properties['unit_code'] = DataDefinition::create('string')
      ->setLabel(new TranslatableMarkup('Unit code'))
      ->setDescription(new TranslatableMarkup('Price unit code from dictionary (e.g., /m²/an)'));

    $properties['period_code'] = DataDefinition::create('string')
      ->setLabel(new TranslatableMarkup('Period code'))
      ->setDescription(new TranslatableMarkup('Period code from dictionary (e.g., year, month)'));

    $properties['is_on_request'] = DataDefinition::create('boolean')
      ->setLabel(new TranslatableMarkup('On request'))
      ->setDescription(new TranslatableMarkup('Price available on request only'));

    $properties['is_from'] = DataDefinition::create('boolean')
      ->setLabel(new TranslatableMarkup('From'))
      ->setDescription(new TranslatableMarkup('Display "From" prefix'));

    $properties['is_vat_excluded'] = DataDefinition::create('boolean')
      ->setLabel(new TranslatableMarkup('VAT excluded'))
      ->setDescription(new TranslatableMarkup('Price excludes VAT (HT)'));

    $properties['is_charges_included'] = DataDefinition::create('boolean')
      ->setLabel(new TranslatableMarkup('Charges included'))
      ->setDescription(new TranslatableMarkup('Price includes charges (CC)'));

    return $properties;
  }

  /**
   * {@inheritdoc}
   */
  public static function schema(FieldStorageDefinitionInterface $field_definition): array {
    return [
      'columns' => [
        'amount' => [
          'type' => 'numeric',
          'precision' => 15,
          'scale' => 2,
        ],
        'currency_code' => [
          'type' => 'varchar',
          'length' => 3,
          'default' => 'EUR',
        ],
        'value_type_code' => [
          'type' => 'varchar',
          'length' => 32,
        ],
        'unit_code' => [
          'type' => 'varchar',
          'length' => 64,
        ],
        'period_code' => [
          'type' => 'varchar',
          'length' => 32,
        ],
        'is_on_request' => [
          'type' => 'int',
          'size' => 'tiny',
          'default' => 0,
        ],
        'is_from' => [
          'type' => 'int',
          'size' => 'tiny',
          'default' => 0,
        ],
        'is_vat_excluded' => [
          'type' => 'int',
          'size' => 'tiny',
          'default' => 0,
        ],
        'is_charges_included' => [
          'type' => 'int',
          'size' => 'tiny',
          'default' => 0,
        ],
      ],
      'indexes' => [
        'amount' => ['amount'],
        'currency_code' => ['currency_code'],
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function isEmpty(): bool {
    $amount = $this->get('amount')->getValue();
    $is_on_request = $this->get('is_on_request')->getValue();
    return ($amount === NULL || $amount === '') && !$is_on_request;
  }

  /**
   * {@inheritdoc}
   */
  public static function mainPropertyName(): ?string {
    return 'amount';
  }

}
