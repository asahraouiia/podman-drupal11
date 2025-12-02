<?php

declare(strict_types=1);

namespace Drupal\ps_price\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\Attribute\FieldFormatter;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\ps_price\Service\PriceFormatterInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Plugin implementation of the 'ps_price_short' formatter.
 *
 * @see docs/modules/ps_price.md#formatter
 */
#[FieldFormatter(
  id: 'ps_price_short',
  label: new TranslatableMarkup('Price short'),
  field_types: ['ps_price'],
)]
class PriceShortFormatter extends FormatterBase implements ContainerFactoryPluginInterface {

  /**
   * Constructs a PriceShortFormatter object.
   *
   * @param string $plugin_id
   *   The plugin_id for the formatter.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param mixed $field_definition
   *   The definition of the field to which the formatter is associated.
   * @param array $settings
   *   The formatter settings.
   * @param string $label
   *   The formatter label display setting.
   * @param string $view_mode
   *   The view mode.
   * @param array $third_party_settings
   *   Any third party settings.
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
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition): static {
    return new static(
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
    ] + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state): array {
    $elements = parent::settingsForm($form, $form_state);

    $elements['show_currency'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Show currency'),
      '#default_value' => $this->getSetting('show_currency'),
    ];

    return $elements;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary(): array {
    $summary = [];
    $settings = $this->getSettings();

    if ($settings['show_currency']) {
      $summary[] = $this->t('Display: amount + currency');
    }
    else {
      $summary[] = $this->t('Display: amount only');
    }

    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode): array {
    $elements = [];
    $settings = $this->getSettings();

    foreach ($items as $delta => $item) {
      $elements[$delta] = [
        '#markup' => $this->priceFormatter->formatShort($item, $settings),
      ];
    }

    return $elements;
  }

}
