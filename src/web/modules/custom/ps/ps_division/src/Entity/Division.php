<?php

declare(strict_types=1);

namespace Drupal\ps_division\Entity;

use Drupal\Core\Entity\Attribute\ContentEntityType;
use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityChangedTrait;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\StringTranslation\TranslatableMarkup;

/**
 * Defines the Division entity (minimal regenerated model).
 *
 * Simplified division holding only structural classification and surfaces.
 * All pricing, feature, and diagnostic concerns removed per minimal spec.
 *
 * Performance: O(1) field access; total surface aggregation O(n) where n
 * is number of surface items (typically small).
 *
 * @see \Drupal\ps_division\Entity\DivisionInterface
 * @see docs/specs/08-ps-division.md
 * @see docs/02-modele-donnees-drupal.md#42-entité-ps_division
 */
#[ContentEntityType(
  id: 'ps_division',
  label: new TranslatableMarkup('Division'),
  label_collection: new TranslatableMarkup('Divisions'),
  label_singular: new TranslatableMarkup('division'),
  label_plural: new TranslatableMarkup('divisions'),
  label_count: [
    'singular' => '@count division',
    'plural' => '@count divisions',
  ],
  handlers: [
    'list_builder' => 'Drupal\\ps_division\\DivisionListBuilder',
    'form' => [
      'default' => 'Drupal\\ps_division\\Form\\DivisionForm',
      'add' => 'Drupal\\ps_division\\Form\\DivisionForm',
      'edit' => 'Drupal\\ps_division\\Form\\DivisionForm',
      'delete' => 'Drupal\\ps_division\\Form\\DivisionDeleteForm',
    ],
    'access' => 'Drupal\\ps_division\\DivisionAccessControlHandler',
    'views_data' => 'Drupal\\views\\EntityViewsData',
  ],
  base_table: 'ps_division',
  data_table: 'ps_division_field_data',
  translatable: TRUE,
  admin_permission: 'administer ps_division entities',
  entity_keys: [
    'id' => 'id',
    'uuid' => 'uuid',
    'label' => 'building_name',
    'langcode' => 'langcode',
  ],
  links: [
    'canonical' => '/division/{ps_division}',
    'add-form' => '/admin/ps/structure/divisions/add',
    'edit-form' => '/admin/ps/structure/divisions/{ps_division}/edit',
    'delete-form' => '/admin/ps/structure/divisions/{ps_division}/delete',
    'collection' => '/admin/ps/structure/divisions',
  ],
)]
final class Division extends ContentEntityBase implements DivisionInterface {

  use EntityChangedTrait;

  /**
   * {@inheritdoc}
   */
  public function getEntityId(): ?int {
    $value = $this->get('entity_id')->value;
    return is_numeric($value) ? (int) $value : NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function setEntityId(?int $entity_id): static {
    $this->set('entity_id', $entity_id);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getBuildingName(): string {
    $value = $this->get('building_name')->value;
    return is_string($value) ? $value : '';
  }

  /**
   * {@inheritdoc}
   */
  public function setBuildingName(string $name): static {
    $this->set('building_name', $name);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getLot(): ?string {
    $value = $this->get('lot')->value;
    if ($value === NULL || $value === '') {
      return NULL;
    }
    return is_string($value) ? $value : NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function setLot(?string $lot): static {
    $this->set('lot', $lot);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getAvailability(): ?string {
    $value = $this->get('availability')->value;
    if ($value === NULL || $value === '') {
      return NULL;
    }
    return is_string($value) ? $value : NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function setAvailability(?string $availability): static {
    $this->set('availability', $availability);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getTotalSurface(): float {
    $total = 0.0;
    foreach ($this->get('surfaces') as $item) {
      $value = $item->getValue();
      if (is_numeric($value)) {
        $total += (float) $value;
      }
    }
    return $total;
  }

  /**
   * {@inheritdoc}
   *
   * @return array<string, \Drupal\Core\Field\FieldDefinitionInterface>
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type): array {
    $fields = parent::baseFieldDefinitions($entity_type);

    $fields['langcode'] = BaseFieldDefinition::create('language')
      ->setLabel(new TranslatableMarkup('Language'))
      ->setDescription(new TranslatableMarkup('The language code for the Division entity.'));

    $fields['entity_id'] = BaseFieldDefinition::create('integer')
      ->setLabel(new TranslatableMarkup('Entity ID'))
      ->setDescription(new TranslatableMarkup('Weak reference to parent offer or node (nullable).'))
      ->setRequired(FALSE)
      ->setDefaultValue(NULL)
      ->setSetting('unsigned', TRUE)
      ->setSetting('size', 'big')
      ->setDisplayOptions('view', [
        'label' => 'inline',
        'type' => 'number_integer',
        'weight' => 0,
      ])
      ->setDisplayOptions('form', [
        'type' => 'number',
        'weight' => 0,
      ])
      ->setDisplayConfigurable('view', TRUE)
      ->setDisplayConfigurable('form', TRUE);

    $fields['floor'] = BaseFieldDefinition::create('ps_dictionary')
      ->setLabel(new TranslatableMarkup('Floor'))
      ->setDescription(new TranslatableMarkup('Floor code from floor dictionary (PB, RDC, R+1, etc.).'))
      ->setSetting('dictionary_type', 'floor')
      ->setDisplayOptions('view', [
        'label' => 'inline',
        'type' => 'string',
        'weight' => 5,
      ])
      ->setDisplayOptions('form', [
        'type' => 'string_textfield',
        'weight' => 5,
      ])
      ->setDisplayConfigurable('view', TRUE)
      ->setDisplayConfigurable('form', TRUE);

    $fields['building_name'] = BaseFieldDefinition::create('string')
      ->setLabel(new TranslatableMarkup('Building name'))
      ->setDescription(new TranslatableMarkup('Name of the building containing this division (entity label).'))
      ->setSetting('max_length', 255)
      ->setTranslatable(TRUE)
      ->setRequired(TRUE)
      ->setDisplayOptions('view', [
        'label' => 'inline',
        'type' => 'string',
        'weight' => 6,
      ])
      ->setDisplayOptions('form', [
        'type' => 'string_textfield',
        'weight' => 6,
      ])
      ->setDisplayConfigurable('view', TRUE)
      ->setDisplayConfigurable('form', TRUE);

    $fields['type'] = BaseFieldDefinition::create('ps_dictionary')
      ->setLabel(new TranslatableMarkup('Type'))
      ->setDescription(new TranslatableMarkup('Division type code from surface_type dictionary.'))
      ->setSetting('dictionary_type', 'surface_type')
      ->setDisplayOptions('view', [
        'label' => 'inline',
        'type' => 'string',
        'weight' => 7,
      ])
      ->setDisplayOptions('form', [
        'type' => 'string_textfield',
        'weight' => 7,
      ])
      ->setDisplayConfigurable('view', TRUE)
      ->setDisplayConfigurable('form', TRUE);

    $fields['nature'] = BaseFieldDefinition::create('ps_dictionary')
      ->setLabel(new TranslatableMarkup('Nature'))
      ->setDescription(new TranslatableMarkup('Division nature code from surface_nature dictionary.'))
      ->setSetting('dictionary_type', 'surface_nature')
      ->setDisplayOptions('view', [
        'label' => 'inline',
        'type' => 'string',
        'weight' => 8,
      ])
      ->setDisplayOptions('form', [
        'type' => 'string_textfield',
        'weight' => 8,
      ])
      ->setDisplayConfigurable('view', TRUE)
      ->setDisplayConfigurable('form', TRUE);

    $fields['lot'] = BaseFieldDefinition::create('string')
      ->setLabel(new TranslatableMarkup('Lot'))
      ->setDescription(new TranslatableMarkup('Lot identifier (alphanumeric).'))
      ->setSetting('max_length', 255)
      ->setTranslatable(TRUE)
      ->setDisplayOptions('view', [
        'label' => 'inline',
        'type' => 'string',
        'weight' => 9,
      ])
      ->setDisplayOptions('form', [
        'type' => 'string_textfield',
        'weight' => 9,
      ])
      ->setDisplayConfigurable('view', TRUE)
      ->setDisplayConfigurable('form', TRUE);

    $fields['surfaces'] = BaseFieldDefinition::create('ps_surface')
      ->setLabel(new TranslatableMarkup('Surfaces'))
      ->setDescription(new TranslatableMarkup('Division surfaces with unit and qualification.'))
      ->setCardinality(BaseFieldDefinition::CARDINALITY_UNLIMITED)
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'ps_surface_default',
        'weight' => 10,
      ])
      ->setDisplayOptions('form', [
        'type' => 'ps_surface_default',
        'weight' => 10,
      ])
      ->setDisplayConfigurable('view', TRUE)
      ->setDisplayConfigurable('form', TRUE);

    $fields['availability'] = BaseFieldDefinition::create('text_long')
      ->setLabel(new TranslatableMarkup('Availability'))
      ->setDescription(new TranslatableMarkup('Multilingual availability notes.'))
      ->setTranslatable(TRUE)
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'text_default',
        'weight' => 11,
      ])
      ->setDisplayOptions('form', [
        'type' => 'text_textarea',
        'weight' => 11,
        'settings' => ['rows' => 3],
      ])
      ->setDisplayConfigurable('view', TRUE)
      ->setDisplayConfigurable('form', TRUE);

    $fields['created'] = BaseFieldDefinition::create('created')
      ->setLabel(new TranslatableMarkup('Created'))
      ->setDescription(new TranslatableMarkup('The time that the division was created.'));

    $fields['changed'] = BaseFieldDefinition::create('changed')
      ->setLabel(new TranslatableMarkup('Changed'))
      ->setDescription(new TranslatableMarkup('The time that the division was last edited.'));

    return $fields;
  }

}
