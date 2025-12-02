<?php

declare(strict_types=1);

namespace Drupal\ps_dictionary\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBase;
use Drupal\Core\Entity\Attribute\ConfigEntityType;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\ps_dictionary\Form\DictionaryTypeForm;
use Drupal\ps_dictionary\Form\DictionaryTypeDeleteForm;

/**
 * Defines the Dictionary Type configuration entity.
 *
 * A dictionary type represents a category of business codes
 * (e.g., property_type, transaction_type, offer_status).
 *
 * @see docs/specs/03-ps-dictionary.md#dictionary-types
 */
#[ConfigEntityType(
  id: 'ps_dictionary_type',
  label: new TranslatableMarkup('Dictionary Type'),
  label_collection: new TranslatableMarkup('Dictionary Types'),
  label_singular: new TranslatableMarkup('dictionary type'),
  label_plural: new TranslatableMarkup('dictionary types'),
  label_count: [
    'singular' => '@count dictionary type',
    'plural' => '@count dictionary types',
  ],
  handlers: [
    'list_builder' => DictionaryTypeListBuilder::class,
    'form' => [
      'add' => DictionaryTypeForm::class,
      'edit' => DictionaryTypeForm::class,
      'delete' => DictionaryTypeDeleteForm::class,
    ],
  ],
  config_prefix: 'type',
  admin_permission: 'administer dictionaries',
  entity_keys: [
    'id' => 'id',
    'label' => 'label',
  ],
  config_export: [
    'id',
    'label',
    'description',
    'is_translatable',
    'locked',
    'metadata',
  ],
  links: [
    'collection' => '/admin/ps/dictionaries',
    'add-form' => '/admin/ps/dictionaries/add',
    'edit-form' => '/admin/ps/dictionaries/{ps_dictionary_type}/edit',
    'delete-form' => '/admin/ps/dictionaries/{ps_dictionary_type}/delete',
  ],
)]
class DictionaryType extends ConfigEntityBase implements DictionaryTypeInterface {

  /**
   * The dictionary type ID.
   */
  protected string $id = '';

  /**
   * The dictionary type label.
   */
  protected string $label = '';

  /**
   * The dictionary type description.
   */
  protected ?string $description = NULL;

  /**
   * Whether entries are translatable.
   */
  protected bool $is_translatable = FALSE;

  /**
   * Whether the dictionary type is locked (cannot be deleted).
   */
  protected bool $locked = FALSE;

  /**
   * Additional metadata.
   */
  protected array $metadata = [];

  /**
   * {@inheritdoc}
   */
  public function id(): string {
    return $this->id;
  }

  /**
   * {@inheritdoc}
   */
  public function label(): string {
    return $this->label;
  }

  /**
   * {@inheritdoc}
   */
  public function getDescription(): ?string {
    return $this->description;
  }

  /**
   * {@inheritdoc}
   */
  public function setDescription(?string $description): static {
    $this->description = $description;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function isTranslatable(): bool {
    return $this->is_translatable;
  }

  /**
   * {@inheritdoc}
   */
  public function setTranslatable(bool $translatable): static {
    $this->is_translatable = $translatable;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getMetadata(): array {
    return $this->metadata ?? [];
  }

  /**
   * {@inheritdoc}
   */
  public function setMetadata(array $metadata): static {
    $this->metadata = $metadata;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function isLocked(): bool {
    return $this->locked ?? FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function setLocked(bool $locked): static {
    $this->locked = $locked;
    return $this;
  }

}
