<?php

declare(strict_types=1);

namespace Drupal\ps_diagnostic\Plugin\Field\FieldWidget;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Field\Attribute\FieldWidget;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\WidgetBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Defines the 'ps_diagnostic_default' field widget.
 *
 * Simplified widget for diagnostic data entry with:
 * - Type selection (DPE, GES)
 * - Numeric value input (auto-calculates class)
 * - Manual class override option
 * - Validity dates
 * - Special state checkboxes (no classification, non-applicable).
 *
 * @see docs/specs/07-ps-diagnostic.md
 */
#[FieldWidget(
  id: 'ps_diagnostic_default',
  label: new TranslatableMarkup('Diagnostic default'),
  field_types: ['ps_diagnostic'],
)]
class DiagnosticDefaultWidget extends WidgetBase implements ContainerFactoryPluginInterface {

  /**
   * Constructs a DiagnosticDefaultWidget.
   *
   * @param string $plugin_id
   *   The plugin ID.
   * @param mixed $plugin_definition
   *   The plugin definition.
   * @param \Drupal\Core\Field\FieldDefinitionInterface $field_definition
   *   The field definition.
   * @param array $settings
   *   The widget settings.
   * @param array $third_party_settings
   *   Third party settings.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   Entity type manager.
   */
  public function __construct(
    $plugin_id,
    $plugin_definition,
    FieldDefinitionInterface $field_definition,
    array $settings,
    array $third_party_settings,
    private readonly EntityTypeManagerInterface $entityTypeManager,
  ) {
    parent::__construct($plugin_id, $plugin_definition, $field_definition, $settings, $third_party_settings);
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
      $configuration['third_party_settings'],
      $container->get('entity_type.manager'),
    );
  }

  /**
   * {@inheritdoc}
   *
   * Builds simplified form element for diagnostic field.
   *
   * @param \Drupal\Core\Field\FieldItemListInterface<\Drupal\ps_diagnostic\Plugin\Field\FieldType\DiagnosticItem> $items
   *   The field items.
   * @param int $delta
   *   The order of this item in the array of sub-elements.
   * @param array $element
   *   A form element array.
   * @param array $form
   *   The overall form array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   *
   * @return array
   *   The form element array.
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state): array {
    $item = $items[$delta];

    $element['#type'] = 'fieldset';
    $element['#title'] = $this->t('Diagnostic #@delta', ['@delta' => $delta + 1]);

    $element['type_id'] = [
      '#type' => 'select',
      '#title' => $this->t('Diagnostic type'),
      '#options' => $this->getDiagnosticTypeOptions(),
      '#default_value' => $item->type_id ?? '',
      '#empty_option' => $this->t('- Select -'),
      '#required' => FALSE,
    ];

    $element['value_numeric'] = [
      '#type' => 'number',
      '#title' => $this->t('Numeric value'),
      '#default_value' => $item->value_numeric ?? NULL,
      '#step' => 0.01,
      '#min' => 0,
      '#description' => $this->t('Numeric value for automatic class calculation.'),
    ];

    $element['label_code'] = [
      '#type' => 'select',
      '#title' => $this->t('Energy class (override)'),
      '#options' => [
        'A' => 'A',
        'B' => 'B',
        'C' => 'C',
        'D' => 'D',
        'E' => 'E',
        'F' => 'F',
        'G' => 'G',
      ],
      '#default_value' => $item->label_code ?? '',
      '#empty_option' => $this->t('- Auto-calculate -'),
      '#description' => $this->t('Manually set the energy class. Leave empty to calculate from value.'),
    ];

    $element['valid_from'] = [
      '#type' => 'date',
      '#title' => $this->t('Diagnostic date'),
      '#default_value' => $item->valid_from ?? '',
      '#description' => $this->t('Date when the diagnostic was performed.'),
    ];

    $element['valid_to'] = [
      '#type' => 'date',
      '#title' => $this->t('Valid until'),
      '#default_value' => $item->valid_to ?? '',
      '#description' => $this->t('Expiration date of the diagnostic.'),
    ];

    $element['no_classification'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('No classification available'),
      '#default_value' => $item->no_classification ?? FALSE,
      '#description' => $this->t('Check if no energy class can be determined (displays "?").'),
    ];

    $element['non_applicable'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Not applicable'),
      '#default_value' => $item->non_applicable ?? FALSE,
      '#description' => $this->t('Check if diagnostic is not applicable (displays "N/A").'),
    ];

    return $element;
  }

  /**
   * Gets diagnostic type options for select field.
   *
   * @return array<string, string>
   *   Array of type_id => label.
   */
  protected function getDiagnosticTypeOptions(): array {
    $options = [];

    try {
      $storage = $this->entityTypeManager->getStorage('ps_diagnostic_type');
      $entities = $storage->loadMultiple();

      foreach ($entities as $entity) {
        $options[$entity->id()] = $entity->label();
      }
    }
    catch (\Exception $e) {
      // Return empty options if error.
    }

    return $options;
  }

}
