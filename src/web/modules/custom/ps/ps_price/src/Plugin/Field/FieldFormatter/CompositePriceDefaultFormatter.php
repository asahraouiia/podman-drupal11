<?php

declare(strict_types=1);

namespace Drupal\ps_price\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\Attribute\FieldFormatter;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FormatterBase;
use Drupal\Core\StringTranslation\TranslatableMarkup;

/**
 * Default formatter for composite price.
 */
#[FieldFormatter(
  id: 'ps_composite_price_default',
  label: new TranslatableMarkup('Composite price'),
  field_types: ['ps_composite_price'],
)]
final class CompositePriceDefaultFormatter extends FormatterBase {
  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode): array {
    $elements = [];
    foreach ($items as $delta => $item) {
      $isDivisible = (bool) ($item->is_divisible ?? FALSE);
      if ($isDivisible) {
        $prices = [];
        if (!empty($item->prices)) {
          $decoded = json_decode((string) $item->prices, TRUE);
          if (is_array($decoded)) {
            $prices = $decoded;
          }
        }
        $elements[$delta] = [
          '#type' => 'inline_template',
          '#template' => '{{ prices|join(", ") }}',
          '#context' => ['prices' => $prices],
        ];
      }
      else {
        $elements[$delta] = [
          '#type' => 'inline_template',
          '#template' => '{{ total }}',
          '#context' => ['total' => $item->total ?? NULL],
        ];
      }
    }
    return $elements;
  }
}
