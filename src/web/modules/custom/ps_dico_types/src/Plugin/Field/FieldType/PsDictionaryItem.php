<?php

namespace Drupal\ps_dico_types\Plugin\Field\FieldType;

use Drupal\Core\Field\FieldItemBase;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\TypedData\DataDefinition;
use Drupal\ps_dico_types\PsDicoInterface;

/**
 * Plugin implementation of the 'ps_dictionary' field type.
 *
 * @FieldType(
 *   id = "ps_dictionary",
 *   label = @Translation("Dictionary"),
 *   description = @Translation("Stores a reference to a dictionary item (ps_dico)."),
 *   default_widget = "ps_dictionary_select",
 *   default_formatter = "ps_dictionary_label"
 * )
 */
class PsDictionaryItem extends FieldItemBase {

  /**
   * Cached loaded dictionary entity for this field item.
   *
   * @var \Drupal\ps_dico_types\PsDicoInterface|null
   */
  protected ?PsDicoInterface $entity = NULL;

  /**
   * {@inheritdoc}
   */
  public static function defaultStorageSettings() {
    return [
      'dictionary_type' => '',
    ] + parent::defaultStorageSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function storageSettingsForm(array &$form, FormStateInterface $form_state, $has_data) {
    $elements = [];

    // Load available dictionary types.
    $entity_type_manager = \Drupal::entityTypeManager();
    $types = $entity_type_manager->getStorage('ps_dico_type')->loadMultiple();
    $type_options = ['' => $this->t('- Select a dictionary type -')];
    foreach ($types as $type) {
      $type_options[$type->id()] = $type->label();
    }

    $elements['dictionary_type'] = [
      '#type' => 'select',
      '#title' => $this->t('Dictionary Type'),
      '#options' => $type_options,
      '#default_value' => $this->getSetting('dictionary_type'),
      '#required' => TRUE,
      '#disabled' => $has_data,
      '#description' => $this->t('Select the dictionary type to use for this field. Cannot be changed once data exists.'),
    ];

    return $elements;
  }

  /**
   * {@inheritdoc}
   */
  public static function propertyDefinitions(FieldStorageDefinitionInterface $field_definition) {
    $properties['value'] = DataDefinition::create('string')
      ->setLabel(t('Dictionary item ID'))
      ->setDescription(t('The ID of the dictionary item (ps_dico).'))
      ->setRequired(TRUE);

    return $properties;
  }

  /**
   * {@inheritdoc}
   */
  public static function schema(FieldStorageDefinitionInterface $field_definition) {
    return [
      'columns' => [
        'value' => [
          'type' => 'varchar',
          'length' => 255,
          'not null' => FALSE,
        ],
      ],
      'indexes' => [
        'value' => ['value'],
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function isEmpty() {
    $value = $this->get('value')->getValue();
    return $value === NULL || $value === '';
  }

  /**
   * Gets the referenced dictionary item entity.
   *
   * @return \Drupal\ps_dico_types\PsDicoInterface|null
   *   The dictionary item entity, or NULL if not found.
   */
  public function getEntity(): ?PsDicoInterface {
    dump($this->entity);die;
    if ($this->entity !== NULL) {
      return $this->entity;
    }

    $value = $this->get('value')->getValue();
    if (!$value) {
      return NULL;
    }

    $loaded = \Drupal::entityTypeManager()
      ->getStorage('ps_dico')
      ->load($value);

    // Only cache if the loaded entity implements the expected interface.
    $this->entity = $loaded instanceof PsDicoInterface ? $loaded : NULL;
    return $this->entity;
  }

}
