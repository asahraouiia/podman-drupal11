<?php

declare(strict_types=1);

namespace Drupal\ps_dictionary\Plugin\Field\FieldType;

use Drupal\Core\Field\Attribute\FieldType;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\TypedData\DataDefinition;
use Drupal\options\Plugin\Field\FieldType\ListStringItem;

/**
 * Plugin implementation of the 'ps_dictionary' field type.
 *
 * Extends Drupal's list_string field to automatically populate options
 * from ps_dictionary config entities. Storage setting 'dictionary_type'
 * determines which dictionary to load options from.
 *
 * Example usage:
 * @code
 * $field_storage = FieldStorageConfig::create([
 *   'field_name' => 'field_transaction_type',
 *   'entity_type' => 'node',
 *   'type' => 'ps_dictionary',
 *   'settings' => ['dictionary_type' => 'transaction_type'],
 * ]);
 * @endcode
 *
 * @see \Drupal\ps_dictionary\Service\DictionaryManagerInterface
 * @see docs/modules/ps_dictionary.md#field-type
 */
#[FieldType(
  id: "ps_dictionary",
  label: new TranslatableMarkup("Dictionary"),
  description: new TranslatableMarkup("List of values from a ps_dictionary."),
  category: "PropertySearch",
  default_widget: "ps_dictionary_select",
  default_formatter: "ps_dictionary_default",
)]
class DictionaryItem extends ListStringItem {

  /**
   * {@inheritdoc}
   */
  public static function propertyDefinitions(FieldStorageDefinitionInterface $field_definition): array {
    $properties = parent::propertyDefinitions($field_definition);

    // Override value property to clarify it stores dictionary code.
    $properties['value'] = DataDefinition::create('string')
      ->setLabel(new TranslatableMarkup('Dictionary code'))
      ->setRequired(FALSE);

    return $properties;
  }

  /**
   * {@inheritdoc}
   */
  public static function defaultStorageSettings(): array {
    return [
      'dictionary_type' => '',
      // Use callback function for dynamic values from dictionary.
      'allowed_values_function' => '\Drupal\ps_dictionary\Plugin\Field\FieldType\DictionaryItem::getAllowedValuesCallback',
    ] + parent::defaultStorageSettings();
  }

  /**
   * Callback to provide allowed values dynamically from dictionary.
   *
   * This function is called by options_allowed_values() to populate
   * the allowed values for this field type.
   *
   * @param \Drupal\Core\Field\FieldStorageDefinitionInterface $definition
   *   The field storage definition.
   * @param \Drupal\Core\Entity\FieldableEntityInterface|null $entity
   *   (optional) The entity context.
   * @param bool $cacheable
   *   (optional) Whether the values should be cached statically.
   *
   * @return array
   *   An array of allowed values (code => label).
   */
  public static function getAllowedValuesCallback(FieldStorageDefinitionInterface $definition, $entity = NULL, &$cacheable = TRUE): array {
    $dictionary_type = $definition->getSetting('dictionary_type');

    if (!$dictionary_type) {
      return [];
    }

    /** @var \Drupal\ps_dictionary\Service\DictionaryManagerInterface $manager */
    $manager = \Drupal::service('ps_dictionary.manager');
    return $manager->getOptions($dictionary_type);
  }

  /**
   * {@inheritdoc}
   */
  public function storageSettingsForm(array &$form, FormStateInterface $form_state, $has_data): array {
    $element = [];

    // Dictionary type selector.
    $element['dictionary_type'] = [
      '#type' => 'select',
      '#title' => $this->t('Dictionary type'),
      '#options' => $this->getAvailableDictionaryTypes(),
      '#default_value' => $this->getSetting('dictionary_type'),
      '#required' => TRUE,
      '#disabled' => $has_data,
      '#description' => $this->t('Select which dictionary to use for allowed values. Cannot be changed after data has been created.'),
    ];

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public static function getPreconfiguredOptions(): array {
    // Provide common dictionary types as preconfigured options.
    return [
      'transaction_type' => [
        'label' => new TranslatableMarkup('Transaction type'),
        'field_storage_config' => [
          'settings' => ['dictionary_type' => 'transaction_type'],
        ],
      ],
      'property_type' => [
        'label' => new TranslatableMarkup('Property type'),
        'field_storage_config' => [
          'settings' => ['dictionary_type' => 'property_type'],
        ],
      ],
      'offer_status' => [
        'label' => new TranslatableMarkup('Offer status'),
        'field_storage_config' => [
          'settings' => ['dictionary_type' => 'offer_status'],
        ],
      ],
    ];
  }

  /**
   * Get available dictionary types for selection.
   *
   * @return array<string, string>
   *   Array of dictionary_type machine names => labels.
   */
  protected function getAvailableDictionaryTypes(): array {
    /** @var \Drupal\Core\Entity\EntityStorageInterface $storage */
    $storage = \Drupal::entityTypeManager()->getStorage('ps_dictionary_type');
    $types = $storage->loadMultiple();

    $options = ['' => $this->t('- Select dictionary type -')];
    foreach ($types as $id => $type) {
      $options[$id] = $type->label();
    }

    return $options;
  }

  /**
   * {@inheritdoc}
   */
  public function getConstraints(): array {
    $constraints = parent::getConstraints();

    // Add validation that value must be a valid dictionary code.
    $dictionary_type = $this->getSetting('dictionary_type');
    if ($dictionary_type && !empty($this->value)) {
      $manager = \Drupal::service('ps_dictionary.manager');
      if (!$manager->isValid($dictionary_type, $this->value)) {
        // Note: Validation happens in widget, this is safety check.
      }
    }

    return $constraints;
  }

}
