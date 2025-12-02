<?php

declare(strict_types=1);

namespace Drupal\ps_price\Plugin\Field\FieldWidget;

use Drupal\Component\Utility\Html;
use Drupal\Core\Field\Attribute\FieldWidget;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\WidgetBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\ps_dictionary\Service\DictionaryManagerInterface;
use Drupal\ps_price\Service\PriceFormatterInterface;
use Drupal\ps_price\Service\PriceRuleMatcherInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Plugin implementation of the 'ps_price_default' widget.
 *
 * Minimal UX: transaction type, amount/range, currency.
 * Unit, period, flags auto-deduced from transaction type.
 *
 * @see docs/modules/ps_price.md#widget
 */
#[FieldWidget(
  id: 'ps_price_default',
  label: new TranslatableMarkup('Price (simplified)'),
  field_types: ['ps_price'],
)]
class PriceDefaultWidget extends WidgetBase implements ContainerFactoryPluginInterface {

  public function __construct(
    string $plugin_id,
    mixed $plugin_definition,
    FieldDefinitionInterface $field_definition,
    array $settings,
    array $third_party_settings,
    private readonly PriceFormatterInterface $priceFormatter,
    private readonly DictionaryManagerInterface $dictionaryManager,
    private readonly ConfigFactoryInterface $configFactory,
    private readonly PriceRuleMatcherInterface $priceRuleMatcher,
  ) {
    parent::__construct($plugin_id, $plugin_definition, $field_definition, $settings, $third_party_settings);
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition): self {
    return new self(
      $plugin_id,
      $plugin_definition,
      $configuration['field_definition'],
      $configuration['settings'],
      $configuration['third_party_settings'] ?? [],
      $container->get('ps_price.formatter'),
      $container->get('ps_dictionary.manager'),
      $container->get('config.factory'),
      $container->get('ps_price.rule_matcher'),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state): array {
    $item = $items[$delta];
    $field_name = $items->getName();
    $wrapper_id = Html::getId($field_name . '-' . (string) $delta . '-price-wrapper');

    $element['#prefix'] = '<div id="' . $wrapper_id . '">';
    $element['#suffix'] = '</div>';

    // AJAX trigger for external field changes (field_operation_code, etc.).
    // Use a visually-hidden button with an AJAX click handler to ensure
    // Drupal attaches a robust Ajax behavior that can be triggered reliably
    // from JavaScript without relying on a hidden input change event.
    $element['ajax_trigger'] = [
      '#type' => 'button',
      '#value' => $this->t('Refresh price'),
      '#name' => $field_name . '[' . $delta . '][ajax_trigger]',
      '#attributes' => [
        'data-ps-price-ajax-trigger' => $wrapper_id,
        'data-field-name' => $field_name,
        'data-delta' => (string) $delta,
        'class' => ['ps-price-ajax-trigger', 'visually-hidden'],
        'style' => 'display:none;',
      ],
      '#ajax' => [
        'callback' => [self::class, 'ajaxRefresh'],
        'wrapper' => $wrapper_id,
        'event' => 'click',
        'disable-refocus' => TRUE,
      ],
      '#limit_validation_errors' => [],
    ];

    // 1. Detect matching rule from parent entity using PriceRuleMatcher.
    $host = $items->getEntity();
    $matching_rule = NULL;
    $transaction_type = NULL;
    
    if ($host) {
      // Ensure the matcher sees the most recent transaction type selected
      // in the current AJAX submission (form_state), not just persisted
      // entity values. This allows live rule updates on change.
      $conf = $this->configFactory->get('ps_price.settings');
      $candidates = $conf->get('transaction_field_candidates') ?? [
        'field_transaction_type',
        'field_operation_code',
      ];

      // Read from user input (POST data) during AJAX, not processed values.
      $user_input = $form_state->getUserInput();

      foreach ($candidates as $candidate) {
        $code = NULL;
        $found = FALSE;
        
        // Try to get from POST data first (for AJAX).
        if (isset($user_input[$candidate][0]['value'])) {
          $code = (string) $user_input[$candidate][0]['value'];
          $found = TRUE;
        }
        elseif (isset($user_input[$candidate][0])) {
          $code = (string) $user_input[$candidate][0];
          $found = TRUE;
        }
        
        // Fallback to form_state processed values.
        if (!$found) {
          $candidate_value = $form_state->getValue($candidate);
          if (is_array($candidate_value) && !empty($candidate_value)) {
            $first = reset($candidate_value);
            $code = is_array($first) && isset($first['value']) ? (string) $first['value'] : NULL;
            $found = TRUE;
          }
        }

        // If we found a field value (even empty string = "Aucun"), process it.
        if ($found) {
          if ($host->hasField($candidate)) {
            // Set on host even if empty (to clear previous selection).
            $host->set($candidate, $code !== '' ? $code : NULL);
          }
          break;
        }
      }

      $matching_rule = $this->priceRuleMatcher->getMatchingRule($host);
      if ($matching_rule) {
        $transaction_type = $matching_rule->getTransactionType();
      }
    }
    $has_matching_rule = $matching_rule !== NULL;

    // 2. Value type (MIN/MAX from XML).
    $value_type_options = $this->dictionaryManager->getOptions('price_value_type');
    $element['value_type_code'] = [
      '#type' => 'select',
      '#title' => $this->t('Type de valeur'),
      '#options' => ['' => $this->t('- Aucun -')] + $value_type_options,
      '#default_value' => $item->value_type_code ?? '',
      '#weight' => -15,
      '#access' => !empty($value_type_options),
    ];

    // 3. On request toggle.
    $element['is_on_request'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Sur demande'),
      '#default_value' => (bool) ($item->is_on_request ?? FALSE),
      '#weight' => -10,
      '#ajax' => [
        'callback' => [self::class, 'ajaxRefresh'],
        'wrapper' => $wrapper_id,
        'disable-refocus' => TRUE,
      ],
      '#limit_validation_errors' => [],
    ];

    // 4. Amount (no range: min/max removed from UI).
    $element['amount'] = [
      '#type' => 'number',
      '#title' => $this->t('Montant'),
      '#default_value' => $item->amount ?? NULL,
      '#step' => '0.01',
      '#min' => 0,
      '#states' => [
        'visible' => [
          [":input[name='" . $field_name . "[$delta][is_on_request]']" => ['checked' => FALSE]],
        ],
      ],
    ];

    // 5. Currency (always hidden, from rule or default).
    $currency_value = $item->currency_code ?? 'EUR';
    if ($matching_rule && $matching_rule->getCurrencyCode()) {
      $currency_value = $matching_rule->getCurrencyCode();
    }
    $element['currency_code'] = [
      '#type' => 'hidden',
      '#default_value' => $currency_value,
    ];

    // 7. Unit/Period: always visible, disabled if rule exists.
    $unit_options = $this->dictionaryManager->getOptions('price_unit');
    $period_options = $this->dictionaryManager->getOptions('price_period');
    
    if ($has_matching_rule && $matching_rule) {
      // Rule exists → show fields as disabled/readonly with rule values.
      $element['unit_code'] = [
        '#type' => 'select',
        '#title' => $this->t('Unité'),
        '#options' => ['' => $this->t('- Aucun -')] + $unit_options,
        '#default_value' => $matching_rule->getUnitCode() ?? ($item->unit_code ?? ''),
        '#disabled' => TRUE,
        '#weight' => 20,
        '#description' => $this->t('Valeur définie automatiquement par la règle.'),
      ];
      $element['period_code'] = [
        '#type' => 'select',
        '#title' => $this->t('Période'),
        '#options' => ['' => $this->t('- Aucun -')] + $period_options,
        '#default_value' => $matching_rule->getPeriodCode() ?? ($item->period_code ?? ''),
        '#disabled' => TRUE,
        '#weight' => 21,
        '#description' => $this->t('Valeur définie automatiquement par la règle.'),
      ];
    }
    else {
      // No rule → show fields for manual entry.
      $element['unit_code'] = [
        '#type' => 'select',
        '#title' => $this->t('Unité'),
        '#options' => ['' => $this->t('- Aucun -')] + $unit_options,
        '#default_value' => $item->unit_code ?? '',
        '#weight' => 20,
      ];
      $element['period_code'] = [
        '#type' => 'select',
        '#title' => $this->t('Période'),
        '#options' => ['' => $this->t('- Aucun -')] + $period_options,
        '#default_value' => $item->period_code ?? '',
        '#weight' => 21,
      ];
    }

    // 7. Contextual flags based on transaction_type from rule.
    $conf = $this->configFactory->get('ps_price.settings');
    $flag_visibility = (array) $conf->get('flag_visibility');
    $visible_flags = $transaction_type ? ($flag_visibility[$transaction_type] ?? []) : [];

    // is_from (common to all).
    if (in_array('is_from', $visible_flags, TRUE)) {
      $element['is_from'] = [
        '#type' => 'checkbox',
        '#title' => $this->t('À partir de'),
        '#default_value' => (int) ($item->is_from ?? FALSE),
        '#weight' => 30,
      ];
    }
    else {
      $element['is_from'] = ['#type' => 'hidden', '#default_value' => (int) ($item->is_from ?? FALSE)];
    }

    // is_vat_excluded (VEN only).
    if (in_array('is_vat_excluded', $visible_flags, TRUE)) {
      $element['is_vat_excluded'] = [
        '#type' => 'checkbox',
        '#title' => $this->t('HT (TVA non incluse)'),
        '#default_value' => (int) ($item->is_vat_excluded ?? FALSE),
        '#weight' => 31,
      ];
    }
    else {
      $element['is_vat_excluded'] = ['#type' => 'hidden', '#default_value' => (int) ($item->is_vat_excluded ?? FALSE)];
    }

    // is_charges_included (LOC only).
    if (in_array('is_charges_included', $visible_flags, TRUE)) {
      $element['is_charges_included'] = [
        '#type' => 'checkbox',
        '#title' => $this->t('Charges comprises'),
        '#default_value' => (int) ($item->is_charges_included ?? FALSE),
        '#weight' => 32,
      ];
    }
    else {
      $element['is_charges_included'] = ['#type' => 'hidden', '#default_value' => (int) ($item->is_charges_included ?? FALSE)];
    }

    // is_on_request always in visible_flags.
    if (!in_array('is_on_request', $visible_flags, TRUE)) {
      // Already added above, just ensure it exists.
    }

    // 7. Preview.
    $element['preview'] = [
      '#type' => 'item',
      '#title' => $this->t('Aperçu'),
      '#markup' => '<em>' . $this->buildPreview($item) . '</em>',
      '#weight' => 100,
    ];

    // Attach library and pass config to JavaScript.
    $conf = $this->configFactory->get('ps_price.settings');
    $element['#attached']['library'][] = 'ps_price/widget';
    $element['#attached']['drupalSettings']['ps_price']['transaction_field_candidates'] = $conf->get('transaction_field_candidates') ?? [
      'field_transaction_type',
      'field_operation_code',
    ];
    $element['#attached']['drupalSettings']['ps_price']['wrapper_id'][$field_name][$delta] = $wrapper_id;

    return $element;
  }

  /**
   * AJAX callback for widget refresh (is_on_request toggle or external trigger).
   */
  public static function ajaxRefresh(array &$form, FormStateInterface $form_state): array {
    $trigger = $form_state->getTriggeringElement();
    
    // Try to get field_name and delta from the trigger button's custom attributes.
    $field_name = $trigger['#attributes']['data-field-name'] ?? '';
    $delta = isset($trigger['#attributes']['data-delta']) 
      ? (int) $trigger['#attributes']['data-delta'] 
      : 0;
    
    // Fallback to array_parents if not found.
    if (empty($field_name)) {
      $parents = $trigger['#array_parents'] ?? [];
      if (isset($parents[0])) {
        $field_name = (string) $parents[0];
        $delta = isset($parents[2]) && is_numeric($parents[2]) ? (int) $parents[2] : 0;
      }
    }

    return $form[$field_name]['widget'][$delta] ?? [];
  }

  /**
   * Build preview from item.
   */
  private function buildPreview($item): string {
    if (!empty($item->is_on_request)) {
      return (string) $this->t('Prix sur demande');
    }

    $parts = [];
    $amount = $item->amount ?? NULL;

    if ($amount !== NULL) {
      $parts[] = number_format((float) $amount, 0, ',', ' ');
    }

    $currency = ($item->currency_code ?? 'EUR') === 'EUR' ? '€' : ($item->currency_code ?? '');
    if ($parts) {
      $parts[0] .= ' ' . $currency;
    }

    $unit = match ($item->unit_code ?? '') {
      'GLOBAL' => '',
      'M2_YEAR' => '/m²',
      'DESK_DAY' => '/bureau',
      default => '',
    };
    $period = match ($item->period_code ?? '') {
      'YEAR' => '/an',
      'DAY' => '/jour',
      default => '',
    };

    if ($unit) {
      $parts[] = $unit . $period;
    }

    if (!empty($item->is_vat_excluded)) {
      $parts[] = 'HT';
    }
    if (!empty($item->is_charges_included)) {
      $parts[] = 'HC';
    }

    return implode(' ', $parts) ?: '—';
  }

  /**
   * Build preview from form values.
   */
  private static function buildPreviewFromValues(array $v, string $unit, string $period, bool $vat_excluded, bool $charges_included): string {
    if (!empty($v['is_on_request'])) {
      return (string) new TranslatableMarkup('Prix sur demande');
    }

    $parts = [];
    $amount = $v['amount'] ?? NULL;

    if ($amount !== NULL && $amount !== '') {
      $parts[] = number_format((float) $amount, 0, ',', ' ');
    }

    $currency = ($v['currency_code'] ?? 'EUR') === 'EUR' ? '€' : ($v['currency_code'] ?? '');
    if ($parts) {
      $parts[0] .= ' ' . $currency;
    }

    $unit_label = match ($unit) {
      'GLOBAL' => '',
      'M2_YEAR' => '/m²',
      'DESK_DAY' => '/bureau',
      default => '',
    };
    $period_label = match ($period) {
      'YEAR' => '/an',
      'DAY' => '/jour',
      default => '',
    };

    if ($unit_label) {
      $parts[] = $unit_label . $period_label;
    }

    if ($vat_excluded) {
      $parts[] = 'HT';
    }
    if ($charges_included) {
      $parts[] = 'HC';
    }

    return implode(' ', $parts) ?: '—';
  }

}
