<?php

declare(strict_types=1);

namespace Drupal\ps_dictionary\Plugin\Field\FieldWidget;

use Drupal\Core\Field\Attribute\FieldWidget;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\Plugin\Field\FieldWidget\OptionsSelectWidget;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\ps_dictionary\Service\DictionaryManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Plugin implementation of the 'ps_dictionary_select' widget.
 *
 * Automatically populates select options from the dictionary specified
 * in the field storage settings. Extends Drupal's OptionsSelectWidget
 * but overrides option loading to use DictionaryManager.
 *
 * @see \Drupal\ps_dictionary\Plugin\Field\FieldType\DictionaryItem
 * @see \Drupal\ps_dictionary\Service\DictionaryManagerInterface
 */
#[FieldWidget(
  id: "ps_dictionary_select",
  label: new TranslatableMarkup("Select list (dictionary)"),
  field_types: ["ps_dictionary"],
)]
class DictionarySelectWidget extends OptionsSelectWidget implements ContainerFactoryPluginInterface {

  /**
   * Constructs a DictionarySelectWidget object.
   *
   * @param string $plugin_id
   *   The plugin_id for the widget.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Field\FieldDefinitionInterface $field_definition
   *   The definition of the field to which the widget is associated.
   * @param array $settings
   *   The widget settings.
   * @param array $third_party_settings
   *   Any third party settings.
   * @param \Drupal\ps_dictionary\Service\DictionaryManagerInterface $dictionaryManager
   *   The dictionary manager service.
   */
  public function __construct(
    $plugin_id,
    $plugin_definition,
    $field_definition,
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
      $configuration['third_party_settings'] ?? [],
      $container->get('ps_dictionary.manager'),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state): array {
    // Let parent handle everything - it will use allowed_values_function
    // automatically via getOptionsProvider().
    $element = parent::formElement($items, $delta, $element, $form, $form_state);

    // Add helpful description if dictionary type is not configured.
    $dictionary_type = $this->fieldDefinition
      ->getFieldStorageDefinition()
      ->getSetting('dictionary_type');
    
    if (!$dictionary_type) {
      $element['#description'] = $this->t('Dictionary type not configured. Please configure the field storage settings.');
    }

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function massageFormValues(array $values, array $form, FormStateInterface $form_state): array {
    $massaged = parent::massageFormValues($values, $form, $form_state);

    // Fix: parent returns nested array structure [0][0][value] instead of [0][value]
    // Flatten the structure for single-value fields
    $fixed = [];
    foreach ($massaged as $delta => $item) {
      if (is_array($item) && isset($item[0]) && is_array($item[0])) {
        // Nested structure - extract the inner array
        $fixed[$delta] = $item[0];
      }
      else {
        $fixed[$delta] = $item;
      }
    }

    return $fixed;
  }

}
