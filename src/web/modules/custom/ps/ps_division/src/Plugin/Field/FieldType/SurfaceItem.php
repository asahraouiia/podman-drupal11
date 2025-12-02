<?php

declare(strict_types=1);

namespace Drupal\ps_division\Plugin\Field\FieldType;

use Drupal\Core\Field\Attribute\FieldType;
use Drupal\Core\Field\FieldItemBase;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\TypedData\DataDefinition;

/**
 * Defines the 'ps_surface' field type.
 *
 * Stores surface measurements with value, unit, type, nature, and
 * qualification. All codes validated against ps_dictionary:
 * - unit: dictionary 'surface_unit' (M2, HA, etc.)
 * - type: dictionary 'surface_type' (APPT, BUREAU, LOCAL, etc.)
 * - nature: dictionary 'surface_nature' (INT, EXT, HABIT, etc.)
 * - qualification: dictionary 'surface_qualification' (DISPO, LOUE, etc.)
 *
 * Performance: O(1) storage; dictionary validation cached.
 *
 * @see docs/specs/08-ps-division.md#32-field-type-ps_surface
 * @see docs/02-modele-donnees-drupal.md#3-field-types-custom
 */
#[FieldType(
  id: 'ps_surface',
  label: new TranslatableMarkup('Surface'),
  description: new TranslatableMarkup('Stores surface with value, unit, type, nature, and qualification.'),
  category: 'PropertySearch',
  default_widget: 'ps_surface_default',
  default_formatter: 'ps_surface_default',
)]
final class SurfaceItem extends FieldItemBase {

  /**
   * {@inheritdoc}
   */
  public static function propertyDefinitions(FieldStorageDefinitionInterface $field_definition): array {
    $properties = [];

    $properties['value'] = DataDefinition::create('float')
      ->setLabel(new TranslatableMarkup('Value'))
      ->setDescription(new TranslatableMarkup('Surface value in specified unit.'))
      ->setRequired(TRUE);

    $properties['unit'] = DataDefinition::create('string')
      ->setLabel(new TranslatableMarkup('Unit'))
      ->setDescription(new TranslatableMarkup('Unit code from surface_unit dictionary (M2, HA, etc.).'))
      ->setRequired(TRUE);

    $properties['type'] = DataDefinition::create('string')
      ->setLabel(new TranslatableMarkup('Type'))
      ->setDescription(new TranslatableMarkup('Type code from surface_type dictionary.'))
      ->setRequired(FALSE);

    $properties['nature'] = DataDefinition::create('string')
      ->setLabel(new TranslatableMarkup('Nature'))
      ->setDescription(new TranslatableMarkup('Nature code from surface_nature dictionary.'))
      ->setRequired(FALSE);

    $properties['qualification'] = DataDefinition::create('string')
      ->setLabel(new TranslatableMarkup('Qualification'))
      ->setDescription(new TranslatableMarkup('Qualification code from surface_qualification dictionary.'))
      ->setRequired(FALSE);

    return $properties;
  }

  /**
   * {@inheritdoc}
   */
  public static function schema(FieldStorageDefinitionInterface $field_definition): array {
    return [
      'columns' => [
        'value' => [
          'type' => 'numeric',
          'precision' => 10,
          'scale' => 2,
          'not null' => FALSE,
        ],
        'unit' => [
          'type' => 'varchar',
          'length' => 10,
          'not null' => FALSE,
        ],
        'type' => [
          'type' => 'varchar',
          'length' => 50,
          'not null' => FALSE,
        ],
        'nature' => [
          'type' => 'varchar',
          'length' => 50,
          'not null' => FALSE,
        ],
        'qualification' => [
          'type' => 'varchar',
          'length' => 32,
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
  public function isEmpty(): bool {
    $value = $this->get('value')->getValue();
    return $value === NULL || $value === '';
  }

  /**
   * Gets the surface value.
   *
   * @return float|null
   *   Surface value or NULL.
   */
  public function getValue(): ?float {
    $v = $this->get('value')->getValue();
    return is_numeric($v) ? (float) $v : NULL;
  }

  /**
   * Gets the unit code.
   *
   * @return string|null
   *   Unit code or NULL.
   */
  public function getUnit(): ?string {
    $value = $this->get('unit')->getValue();
    if ($value === NULL || $value === '') {
      return NULL;
    }
    return is_string($value) ? $value : NULL;
  }

  /**
   * Gets the type code.
   *
   * @return string|null
   *   Type code or NULL.
   */
  public function getType(): ?string {
    $value = $this->get('type')->getValue();
    if ($value === NULL || $value === '') {
      return NULL;
    }
    return is_string($value) ? $value : NULL;
  }

  /**
   * Gets the nature code.
   *
   * @return string|null
   *   Nature code or NULL.
   */
  public function getNature(): ?string {
    $value = $this->get('nature')->getValue();
    if ($value === NULL || $value === '') {
      return NULL;
    }
    return is_string($value) ? $value : NULL;
  }

  /**
   * Gets the qualification code.
   *
   * @return string|null
   *   Qualification code or NULL.
   */
  public function getQualification(): ?string {
    $value = $this->get('qualification')->getValue();
    if ($value === NULL || $value === '') {
      return NULL;
    }
    return is_string($value) ? $value : NULL;
  }

}
