<?php

declare(strict_types=1);

namespace Drupal\ps_diagnostic\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\Attribute\FieldFormatter;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\ps_diagnostic\Service\DiagnosticClassCalculatorInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Defines the 'ps_diagnostic_default' field formatter.
 *
 * Displays diagnostic data with colored energy class bars similar to official
 * DPE/GES displays. Supports special states (?, N/A) and auto-calculated classes.
 *
 * @see docs/specs/07-ps-diagnostic.md
 */
/**
 * PHPStan generic type annotation.
 *
 * @phpstan-extends FormatterBase<\Drupal\Core\Field\FieldItemListInterface<\Drupal\ps_diagnostic\Plugin\Field\FieldType\DiagnosticItem>>
 */
#[FieldFormatter(
  id: 'ps_diagnostic_default',
  label: new TranslatableMarkup('Diagnostic default'),
  field_types: ['ps_diagnostic'],
)]
class DiagnosticDefaultFormatter extends FormatterBase implements ContainerFactoryPluginInterface {

  /**
   * Constructs a DiagnosticDefaultFormatter.
   *
   * @param string $plugin_id
   *   The plugin ID.
   * @param mixed $plugin_definition
   *   The plugin definition.
   * @param \Drupal\Core\Field\FieldDefinitionInterface $field_definition
   *   The field definition.
   * @param array $settings
   *   The formatter settings.
   * @param string $label
   *   The formatter label.
   * @param string $view_mode
   *   The view mode.
   * @param array $third_party_settings
   *   Third party settings.
   * @param \Drupal\ps_diagnostic\Service\DiagnosticClassCalculatorInterface $classCalculator
   *   Diagnostic class calculator service.
   */
  public function __construct(
    $plugin_id,
    $plugin_definition,
    FieldDefinitionInterface $field_definition,
    array $settings,
    $label,
    $view_mode,
    array $third_party_settings,
    private readonly DiagnosticClassCalculatorInterface $classCalculator,
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
      $container->get('ps_diagnostic.class_calculator'),
    );
  }

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings(): array {
    return [
      'show_value' => TRUE,
      'show_dates' => FALSE,
      'layout' => 'horizontal',
      // When TRUE and item has neither numeric value nor class label, component
      // is visually dimmed (disabled look).
      'dim_empty' => TRUE,
      // Opacity percentage (10-90) applied when dim_empty condition matches.
      'dim_opacity' => 30,
    ] + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state): array {
    $elements = parent::settingsForm($form, $form_state);

    $elements['show_value'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Show numeric value'),
      '#default_value' => $this->getSetting('show_value'),
      '#description' => $this->t('Display the numeric value below the class (e.g., "348 kWh/m²/an").'),
    ];

    $elements['show_dates'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Show validity dates'),
      '#default_value' => $this->getSetting('show_dates'),
    ];

    $elements['layout'] = [
      '#type' => 'select',
      '#title' => $this->t('Layout'),
      '#options' => [
        'horizontal' => $this->t('Horizontal bar'),
        'vertical' => $this->t('Vertical list'),
        'compact' => $this->t('Compact (class only)'),
      ],
      '#default_value' => $this->getSetting('layout'),
    ];

    $elements['dim_empty'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Dim when empty (disabled style)'),
      '#default_value' => $this->getSetting('dim_empty'),
      '#description' => $this->t('Apply reduced opacity when there is no numeric value and no determined class.'),
    ];

    $elements['dim_opacity'] = [
      '#type' => 'number',
      '#title' => $this->t('Dim opacity (%)'),
      '#default_value' => $this->getSetting('dim_opacity'),
      '#min' => 10,
      '#max' => 90,
      '#step' => 1,
      '#states' => [
        'visible' => [
          ':input[name="fields[' . $this->fieldDefinition->getName() . '][settings_edit_form][settings][dim_empty]"]' => ['checked' => TRUE],
        ],
      ],
      '#description' => $this->t('Opacity used when dimmed. 30% is recommended.'),
    ];

    return $elements;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary(): array {
    $summary = [];

    $summary[] = $this->getSetting('show_value')
      ? $this->t('Show value')
      : $this->t('Class only');

    $summary[] = $this->t('Layout: @layout', [
      '@layout' => $this->getSetting('layout'),
    ]);

    if ($this->getSetting('dim_empty')) {
      $summary[] = $this->t('Dim empty (@opacity%)', [
        '@opacity' => $this->getSetting('dim_opacity'),
      ]);
    }

    return $summary;
  }

  /**
   * {@inheritdoc}
   *
   * Renders diagnostic field items as colored energy class displays.
   *
   * @param \Drupal\Core\Field\FieldItemListInterface<\Drupal\ps_diagnostic\Plugin\Field\FieldType\DiagnosticItem> $items
   *   The field items to render.
   * @param string $langcode
   *   The language code.
   *
   * @return array
   *   Render array.
   */
  public function viewElements(FieldItemListInterface $items, $langcode): array {
    $elements = [];
    $layout = $this->getSetting('layout');
    $showValue = $this->getSetting('show_value');
    $showDates = $this->getSetting('show_dates');
    $dimEmpty = (bool) $this->getSetting('dim_empty');
    $dimOpacity = (int) $this->getSetting('dim_opacity');

    foreach ($items as $delta => $item) {
      /** @var \Drupal\ps_diagnostic\Plugin\Field\FieldType\DiagnosticItem $item */
      $diagnosticData = [
        'type_id' => $item->type_id,
        'value_numeric' => $item->value_numeric,
        'label_code' => $item->label_code,
        'valid_from' => $item->valid_from,
        'valid_to' => $item->valid_to,
        'no_classification' => $item->no_classification,
        'non_applicable' => $item->non_applicable,
      ];

      $displayInfo = $this->classCalculator->getDisplayInfo($diagnosticData);
      
      // Load diagnostic type to get all classes and label.
      $diagnosticType = NULL;
      $allClasses = [];
      if (!empty($item->type_id)) {
        try {
          $storage = \Drupal::entityTypeManager()->getStorage('ps_diagnostic_type');
          $diagnosticType = $storage->load($item->type_id);
          if ($diagnosticType) {
            $allClasses = $diagnosticType->getClasses();
          }
        }
        catch (\Exception $e) {
          // Silent fail.
        }
      }

      // Determine dimming state (no numeric value and no class label).
      $hasValue = $item->value_numeric !== NULL && $item->value_numeric !== '';
      $hasClass = !empty($item->label_code);
      $isDimmed = $dimEmpty && !$hasValue && !$hasClass;

      $elements[$delta] = [
        '#theme' => 'ps_diagnostic_display',
        '#display_info' => $displayInfo,
        '#value' => $showValue ? $item->value_numeric : NULL,
        '#valid_from' => $showDates ? $item->valid_from : NULL,
        '#valid_to' => $showDates ? $item->valid_to : NULL,
        '#layout' => $layout,
        '#diagnostic_type' => $diagnosticType,
        '#all_classes' => $allClasses,
        '#is_dimmed' => $isDimmed,
        '#dim_opacity' => $dimOpacity,
        '#attached' => [
          'library' => ['ps_diagnostic/diagnostic_display'],
        ],
      ];
    }

    return $elements;
  }

}
