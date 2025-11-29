<?php

namespace Drupal\ps_dico_types\Plugin\Field\FieldType;

use Drupal\Core\Field\FieldItemBase;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\TypedData\DataDefinition;

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
  public function getEntity() {
    $value = $this->get('value')->getValue();
    if ($value) {
      return \Drupal::entityTypeManager()
        ->getStorage('ps_dico')
        ->load($value);
    }
    return NULL;
  }

}
