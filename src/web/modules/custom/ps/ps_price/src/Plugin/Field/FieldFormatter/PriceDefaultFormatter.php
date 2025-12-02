<?php

declare(strict_types=1);

namespace Drupal\ps_price\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\Attribute\FieldFormatter;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\ps_price\Service\PriceFormatterInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Plugin implementation of the 'ps_price_default' formatter.
 *
 * Provides default price display with configurable elements.
 *
 * @see docs/modules/ps_price.md#formatters
 */
#[FieldFormatter(
  id: 'ps_price_default',
  label: new TranslatableMarkup('Default'),
  field_types: ['ps_price'],
)]
final class PriceDefaultFormatter extends FormatterBase {

  /**
   * Constructs a PriceDefaultFormatter object.
   *
   * @param string $plugin_id
   *   The plugin ID.
   * @param mixed $plugin_definition
   *   The plugin definition.
   * @param \Drupal\Core\Field\FieldDefinitionInterface $field_definition
   *   The field definition.
   * @param array<string, mixed> $settings
   *   The formatter settings.
   * @param string $label
   *   The label display setting.
   * @param string $view_mode
   *   The view mode.
   * @param array<string, mixed> $third_party_settings
   *   Third party settings.
   * @param \Drupal\ps_price\Service\PriceFormatterInterface $priceFormatter
   *   The price formatter service.
   */
  public function __construct(
    $plugin_id,
    $plugin_definition,
    $field_definition,
    array $settings,
    $label,
    $view_mode,
    array $third_party_settings,
    private readonly PriceFormatterInterface $priceFormatter,
  ) {
    parent::__construct($plugin_id, $plugin_definition, $field_definition, $settings, $label, $view_mode, $third_party_settings);
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
      $configuration['label'],
      $configuration['view_mode'],
      $configuration['third_party_settings'],
      $container->get('ps_price.formatter'),
    );
  }

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings(): array {
    return [
      'show_currency' => TRUE,
      'show_unit' => TRUE,
      'show_period' => TRUE,
      'show_flags' => TRUE,
    ] + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state): array {
    return [
      'show_currency' => [
        '#type' => 'checkbox',
        '#title' => $this->t('Show currency'),
        '#default_value' => $this->getSetting('show_currency'),
      ],
      'show_unit' => [
        '#type' => 'checkbox',
        '#title' => $this->t('Show unit'),
        '#default_value' => $this->getSetting('show_unit'),
      ],
      'show_period' => [
        '#type' => 'checkbox',
        '#title' => $this->t('Show period'),
        '#default_value' => $this->getSetting('show_period'),
      ],
      'show_flags' => [
        '#type' => 'checkbox',
        '#title' => $this->t('Show flags (HT, CC)'),
        '#default_value' => $this->getSetting('show_flags'),
      ],
    ] + parent::settingsForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary(): array {
    $summary = [];
    $settings = $this->getSettings();

    $enabled = [];
    if ($settings['show_currency']) {
      $enabled[] = $this->t('Currency');
    }
    if ($settings['show_unit']) {
      $enabled[] = $this->t('Unit');
    }
    if ($settings['show_period']) {
      $enabled[] = $this->t('Period');
    }
    if ($settings['show_flags']) {
      $enabled[] = $this->t('Flags');
    }

    if (!empty($enabled)) {
      $summary[] = $this->t('Display: @elements', [
        '@elements' => implode(', ', $enabled),
      ]);
    }
    else {
      $summary[] = $this->t('Display: Amount only');
    }

    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode): array {
    $elements = [];

    foreach ($items as $delta => $item) {
      $elements[$delta] = [
        '#markup' => $this->priceFormatter->format($item, $this->getSettings()),
      ];
    }

    return $elements;
  }

}
