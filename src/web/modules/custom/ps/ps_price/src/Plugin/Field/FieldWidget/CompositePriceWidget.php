<?php

declare(strict_types=1);

namespace Drupal\ps_price\Plugin\Field\FieldWidget;

use Drupal\Core\Field\Attribute\FieldWidget;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\WidgetBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;

/**
 * Widget for composite price.
 */
#[FieldWidget(
  id: 'ps_composite_price_widget',
  label: new TranslatableMarkup('Composite price'),
  field_types: ['ps_composite_price'],
)]
final class CompositePriceWidget extends WidgetBase {
  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state): array {
    $item = $items[$delta];

    $element['is_divisible'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Is divisible'),
      '#default_value' => (bool) ($item->is_divisible ?? FALSE),
    ];

    $element['total'] = [
      '#type' => 'number',
      '#title' => $this->t('Total price'),
      '#default_value' => $item->total ?? NULL,
      '#step' => 0.01,
      '#states' => [
        'visible' => [
          [":input[name='" . $element['#field_name'] . "[$delta][is_divisible]']", 'checked' => FALSE],
        ],
      ],
    ];

    $element['prices'] = [
      '#type' => 'container',
      '#attributes' => ['class' => ['container-inline']],
      '#states' => [
        'visible' => [
          [":input[name='" . $element['#field_name'] . "[$delta][is_divisible]']", 'checked' => TRUE],
        ],
      ],
    ];

    // Build dynamic add-more entries for prices.
    $entered = [];
    if (!empty($item->prices)) {
      $decoded = json_decode((string) $item->prices, TRUE);
      if (is_array($decoded)) {
        $entered = $decoded;
      }
    }
    $count = max(1, count($entered));
    for ($i = 0; $i < $count; $i++) {
      $element['prices']["price_$i"] = [
        '#type' => 'number',
        '#title' => $this->t('Price @i', ['@i' => $i + 1]),
        '#default_value' => $entered[$i] ?? NULL,
        '#step' => 0.01,
      ];
    }

    // Note: dynamic add-more requires proper widget state handling.
    // To avoid runtime errors, we provide 3 inputs by default.
    for ($i = $count; $i < max(3, $count); $i++) {
      $element['prices']["price_$i"] = [
        '#type' => 'number',
        '#title' => $this->t('Price @i', ['@i' => $i + 1]),
        '#default_value' => NULL,
        '#step' => 0.01,
      ];
    }

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function massageFormValues(array $values, array $form, FormStateInterface $form_state): array {
    $massaged = [];
    foreach ($values as $delta => $value) {
      $isDivisible = !empty($value['is_divisible']);
      if ($isDivisible) {
        $prices = [];
        foreach (($value['prices'] ?? []) as $key => $v) {
          if (str_starts_with((string) $key, 'price_') && $v !== '' && $v !== NULL) {
            $prices[] = (float) $v;
          }
        }
        $massaged[$delta] = [
          'is_divisible' => TRUE,
          'total' => NULL,
          'prices' => json_encode($prices),
        ];
      }
      else {
        $massaged[$delta] = [
          'is_divisible' => FALSE,
          'total' => $value['total'] ?? NULL,
          'prices' => json_encode([]),
        ];
      }
    }
    return $massaged;
  }
}
