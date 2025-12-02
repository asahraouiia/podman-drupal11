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
    $element = parent::formElement($items, $delta, $element, $form, $form_state);

    // Load options from dictionary.
    // Read from field STORAGE settings (structural), not instance settings.
    $dictionary_type = $this->fieldDefinition
      ->getFieldStorageDefinition()
      ->getSetting('dictionary_type');
    if ($dictionary_type) {
      $element['#options'] = $this->dictionaryManager->getOptions($dictionary_type);

      // Add empty option if field is not required.
      if (!$this->fieldDefinition->isRequired()) {
        $element['#empty_option'] = $this->t('- None -');
        $element['#empty_value'] = '';
      }

      // If no options are found, provide a helpful note.
      if (empty($element['#options'])) {
        $element['#description'] = $this->t('No entries found for dictionary type @type. Please add entries at /admin/ps/structure/dictionaries/@type/entries.', ['@type' => $dictionary_type]);
      }
    }
    else {
      $element['#options'] = [];
      $element['#description'] = $this->t('Dictionary type not configured in the field storage settings. Edit the field storage to select a dictionary type.');
    }

    return $element;
  }

}
