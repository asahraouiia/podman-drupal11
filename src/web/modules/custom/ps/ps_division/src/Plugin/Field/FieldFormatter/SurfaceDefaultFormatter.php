<?php

declare(strict_types=1);

namespace Drupal\ps_division\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\Attribute\FieldFormatter;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\ps_dictionary\Service\DictionaryManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Plugin implementation of the 'ps_surface_default' formatter.
 *
 * Format: "50.50 m² (Available)" or "50.50 m²" depending on settings.
 *
 * @see docs/specs/08-ps-division.md#32-field-type-ps_surface
 */
#[FieldFormatter(
  id: 'ps_surface_default',
  label: new TranslatableMarkup('Default surface formatter'),
  field_types: ['ps_surface'],
)]
final class SurfaceDefaultFormatter extends FormatterBase {

  /**
   * Constructs a SurfaceDefaultFormatter.
   */
  public function __construct(
    $plugin_id,
    $plugin_definition,
    FieldDefinitionInterface $field_definition,
    array $settings,
    $label,
    $view_mode,
    array $third_party_settings,
    private readonly DictionaryManagerInterface $dictionaryManager,
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
      $container->get('ps_dictionary.manager'),
    );
  }

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings(): array {
    return [
      'show_unit' => TRUE,
      'show_qualification' => TRUE,
    ] + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state): array {
    $elements = parent::settingsForm($form, $form_state);

    $elements['show_unit'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Show unit'),
      '#default_value' => $this->getSetting('show_unit'),
    ];

    $elements['show_qualification'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Show qualification'),
      '#default_value' => $this->getSetting('show_qualification'),
    ];

    return $elements;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary(): array {
    $summary = [];

    $summary[] = $this->getSetting('show_unit')
      ? $this->t('Show unit: Yes')
      : $this->t('Show unit: No');

    $summary[] = $this->getSetting('show_qualification')
      ? $this->t('Show qualification: Yes')
      : $this->t('Show qualification: No');

    return $summary;
  }

  /**
   * {@inheritdoc}
   *
   * @phpstan-ignore-next-line
   */
  public function viewElements(FieldItemListInterface $items, $langcode): array {
    $elements = [];

    foreach ($items as $delta => $item) {
      $value = $item->getValue();
      if ($value === NULL) {
        continue;
      }

      $parts = [number_format($value, 2, '.', ' ')];

      if ($this->getSetting('show_unit') && $item->getUnit()) {
        $unitLabel = $this->dictionaryManager->getLabel('surface_unit', $item->getUnit());
        if ($unitLabel) {
          $parts[] = $unitLabel;
        }
      }

      if ($this->getSetting('show_qualification') && $item->getQualification()) {
        $qualLabel = $this->dictionaryManager->getLabel('surface_qualification', $item->getQualification());
        if ($qualLabel) {
          $parts[] = '(' . $qualLabel . ')';
        }
      }

      $elements[$delta] = [
        '#markup' => implode(' ', $parts),
      ];
    }

    return $elements;
  }

}
