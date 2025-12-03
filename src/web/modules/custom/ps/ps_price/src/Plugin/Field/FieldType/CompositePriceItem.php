<?php

declare(strict_types=1);

namespace Drupal\ps_price\Plugin\Field\FieldType;

use Drupal\Core\Field\Attribute\FieldType;
use Drupal\Core\Field\FieldItemBase;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\TypedData\DataDefinition;

/**
 * Composite price field type: is_divisible, total, prices[].
 */
#[FieldType(
  id: 'ps_composite_price',
  label: new TranslatableMarkup('Composite price'),
  description: new TranslatableMarkup('Stores a total price or multiple prices when divisible.'),
  category: 'PropertySearch',
  default_widget: 'ps_composite_price_widget',
  default_formatter: 'ps_composite_price_default',
)]
final class CompositePriceItem extends FieldItemBase {
  /**
   * {@inheritdoc}
   */
  public static function propertyDefinitions(FieldStorageDefinitionInterface $field_definition): array {
    $properties = [];

    $properties['is_divisible'] = DataDefinition::create('boolean')
      ->setLabel(new TranslatableMarkup('Is divisible'))
      ->setRequired(FALSE);

    $properties['total'] = DataDefinition::create('decimal')
      ->setLabel(new TranslatableMarkup('Total price'))
      ->setRequired(FALSE);

    // Store prices as JSON text to allow unlimited entries.
    $properties['prices'] = DataDefinition::create('string')
      ->setLabel(new TranslatableMarkup('Prices (JSON array of decimals)'))
      ->setRequired(FALSE);

    return $properties;
  }

  /**
   * {@inheritdoc}
   */
  public static function schema(FieldStorageDefinitionInterface $field_definition): array {
    return [
      'columns' => [
        'is_divisible' => [
          'type' => 'int',
          'size' => 'tiny',
          'not null' => FALSE,
        ],
        'total' => [
          'type' => 'numeric',
          'precision' => 14,
          'scale' => 2,
          'not null' => FALSE,
        ],
        'prices' => [
          'type' => 'text',
          'size' => 'big',
          'not null' => FALSE,
        ],
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function isEmpty(): bool {
    $isDivisible = (bool) ($this->get('is_divisible')->getValue() ?? FALSE);
    if ($isDivisible) {
      $prices = $this->get('prices')->getValue();
      if (!is_string($prices) || $prices === '') {
        return TRUE;
      }
      $decoded = json_decode($prices, TRUE);
      return empty($decoded);
    }
    $total = $this->get('total')->getValue();
    return $total === NULL || $total === '';
  }
}
