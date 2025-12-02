<?php

declare(strict_types=1);

namespace Drupal\ps_dictionary\Entity;

use Drupal\Core\Url;
use Drupal\Core\Config\Entity\ConfigEntityBase;
use Drupal\Core\Entity\Attribute\ConfigEntityType;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\ps_dictionary\Form\DictionaryEntryForm;
use Drupal\ps_dictionary\Form\DictionaryEntryDeleteForm;

/**
 * Defines the Dictionary Entry configuration entity.
 *
 * A dictionary entry represents a single code-label pair within a
 * dictionary type. Entries support weighting, status flags, and custom
 * metadata.
 *
 * @see docs/specs/03-ps-dictionary.md#dictionary-entries
 */
#[ConfigEntityType(
  id: 'ps_dictionary_entry',
  label: new TranslatableMarkup('Dictionary Entry'),
  label_collection: new TranslatableMarkup('Dictionary Entries'),
  label_singular: new TranslatableMarkup('dictionary entry'),
  label_plural: new TranslatableMarkup('dictionary entries'),
  label_count: [
    'singular' => '@count dictionary entry',
    'plural' => '@count dictionary entries',
  ],
  handlers: [
    'list_builder' => DictionaryEntryListBuilder::class,
    'form' => [
      'add' => DictionaryEntryForm::class,
      'edit' => DictionaryEntryForm::class,
      'delete' => DictionaryEntryDeleteForm::class,
    ],
  ],
  config_prefix: 'entry',
  admin_permission: 'administer dictionaries',
  entity_keys: [
    'id' => 'id',
    'label' => 'label',
    'uuid' => 'uuid',
    'weight' => 'weight',
  ],
  config_export: [
    'id',
    'dictionary_type',
    'code',
    'label',
    'description',
    'weight',
    'status',
    'deprecated',
    'metadata',
  ],
  // phpcs:ignore Drupal.Files.LineLength.TooLong
  links: [
    'edit-form' => '/admin/ps/dictionaries/{ps_dictionary_type}/entries/{ps_dictionary_entry}/edit',
    'delete-form' => '/admin/ps/dictionaries/{ps_dictionary_type}/entries/{ps_dictionary_entry}/delete',
  ],
)]
class DictionaryEntry extends ConfigEntityBase implements DictionaryEntryInterface {

  /**
   * The entry ID (machine name: type_code).
   */
  protected string $id = '';

  /**
   * The dictionary type ID.
   */
  protected string $dictionary_type = '';

  /**
   * The entry code.
   */
  protected string $code = '';

  /**
   * The entry label.
   */
  protected string $label = '';

  /**
   * The entry description.
   */
  protected ?string $description = NULL;

  /**
   * The weight for sorting.
   */
  protected int $weight = 0;

  /**
   * Whether the entry is deprecated.
   */
  protected bool $deprecated = FALSE;

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
  public function toUrl($rel = 'canonical', array $options = []): Url {
    $url = parent::toUrl($rel, $options);

    // Add ps_dictionary_type parameter to all routes.
    if ($this->dictionary_type) {
      $url->setRouteParameter('ps_dictionary_type', $this->dictionary_type);
    }

    return $url;
  }

  /**
   * {@inheritdoc}
   */
  public function getDictionaryType(): string {
    return $this->dictionary_type;
  }

  /**
   * {@inheritdoc}
   */
  public function setDictionaryType(string $type): static {
    $this->dictionary_type = $type;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getCode(): string {
    return $this->code;
  }

  /**
   * {@inheritdoc}
   */
  public function setCode(string $code): static {
    $this->code = $code;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getLabel(): string {
    return $this->label;
  }

  /**
   * {@inheritdoc}
   */
  public function setLabel(string $label): static {
    $this->label = $label;
    return $this;
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
  public function getWeight(): int {
    return $this->weight;
  }

  /**
   * {@inheritdoc}
   */
  public function setWeight(int $weight): static {
    $this->weight = $weight;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function isActive(): bool {
    return $this->status;
  }

  /**
   * {@inheritdoc}
   */
  public function setStatus($status): static {
    $this->status = (bool) $status;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function isDeprecated(): bool {
    return $this->deprecated;
  }

  /**
   * {@inheritdoc}
   */
  public function setDeprecated(bool $deprecated): static {
    $this->deprecated = $deprecated;
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

}
