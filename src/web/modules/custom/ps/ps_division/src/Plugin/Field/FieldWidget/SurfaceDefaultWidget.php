<?php

declare(strict_types=1);

namespace Drupal\ps_division\Plugin\Field\FieldWidget;

use Drupal\Core\Field\Attribute\FieldWidget;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\WidgetBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\ps_dictionary\Service\DictionaryManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Plugin implementation of the 'ps_surface_default' widget.
 *
 * Two-row layout:
 * - Row 1: Value + Unit (inline)
 * - Row 2: Type + Nature + Qualification (inline)
 *
 * @see docs/specs/08-ps-division.md#32-field-type-ps_surface
 */
#[FieldWidget(
  id: 'ps_surface_default',
  label: new TranslatableMarkup('Default surface widget'),
  field_types: ['ps_surface'],
)]
final class SurfaceDefaultWidget extends WidgetBase {

  /**
   * Constructs a SurfaceDefaultWidget.
   */
  public function __construct(
    $plugin_id,
    $plugin_definition,
    FieldDefinitionInterface $field_definition,
    array $settings,
    array $third_party_settings,
    private readonly DictionaryManagerInterface $dictionaryManager,
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
      $configuration['third_party_settings'],
      $container->get('ps_dictionary.manager'),
    );
  }

  /**
   * {@inheritdoc}
   *
   * @phpstan-ignore-next-line
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state): array {
    $item = $items[$delta];

    // Row 1: Value + Unit inline.
    $element['row_value_unit'] = [
      '#type' => 'container',
      '#attributes' => ['class' => ['container-inline']],
    ];
    $element['row_value_unit']['value'] = [
      '#type' => 'number',
      '#title' => $this->t('Value'),
      '#default_value' => $item->value ?? NULL,
      '#step' => 0.01,
      '#min' => 0,
      '#required' => $element['#required'] ?? FALSE,
    ];
    $element['row_value_unit']['unit'] = [
      '#type' => 'select',
      '#title' => $this->t('Unit'),
      '#options' => ['' => $this->t('- Select -')] + $this->dictionaryManager->getOptions('surface_unit'),
      '#default_value' => $item->unit ?? 'M2',
      '#required' => $element['#required'] ?? FALSE,
    ];

    // Row 2: Type + Nature + Qualification inline.
    $element['row_classification'] = [
      '#type' => 'container',
      '#attributes' => ['class' => ['container-inline']],
    ];
    $element['row_classification']['type'] = [
      '#type' => 'select',
      '#title' => $this->t('Type'),
      '#options' => ['' => $this->t('- None -')] + $this->dictionaryManager->getOptions('surface_type'),
      '#default_value' => $item->type ?? '',
      '#required' => FALSE,
    ];
    $element['row_classification']['nature'] = [
      '#type' => 'select',
      '#title' => $this->t('Nature'),
      '#options' => ['' => $this->t('- None -')] + $this->dictionaryManager->getOptions('surface_nature'),
      '#default_value' => $item->nature ?? '',
      '#required' => FALSE,
    ];
    $element['row_classification']['qualification'] = [
      '#type' => 'select',
      '#title' => $this->t('Qualification'),
      '#options' => ['' => $this->t('- None -')] + $this->dictionaryManager->getOptions('surface_qualification'),
      '#default_value' => $item->qualification ?? 'DISPO',
      '#required' => FALSE,
    ];

    return $element;
  }

}
