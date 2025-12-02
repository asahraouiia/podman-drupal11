<?php

declare(strict_types=1);

namespace Drupal\ps_dictionary\Entity;

use Drupal\Core\Config\Entity\ConfigEntityInterface;

/**
 * Interface for Dictionary Type configuration entities.
 *
 * @see \Drupal\ps_dictionary\Entity\DictionaryType
 */
interface DictionaryTypeInterface extends ConfigEntityInterface {

  /**
   * Gets the description.
   *
   * @return string|null
   *   The description or NULL if not set.
   */
  public function getDescription(): ?string;

  /**
   * Sets the description.
   *
   * @param string|null $description
   *   The description.
   *
   * @return $this
   */
  public function setDescription(?string $description): static;

  /**
   * Checks if entries are translatable.
   *
   * @return bool
   *   TRUE if translatable.
   */
  public function isTranslatable(): bool;

  /**
   * Sets the translatable flag.
   *
   * @param bool $translatable
   *   TRUE to enable translations.
   *
   * @return $this
   */
  public function setTranslatable(bool $translatable): static;

  /**
   * Gets the metadata array.
   *
   * @return array<string, mixed>
   *   The metadata.
   */
  public function getMetadata(): array;

  /**
   * Sets the metadata.
   *
   * @param array<string, mixed> $metadata
   *   The metadata.
   *
   * @return $this
   */
  public function setMetadata(array $metadata): static;

  /**
   * Checks if the dictionary type is locked.
   *
   * Locked dictionaries cannot be deleted to prevent accidental removal
   * of critical business data (e.g., transaction_type, currency).
   *
   * @return bool
   *   TRUE if locked, FALSE otherwise.
   */
  public function isLocked(): bool;

  /**
   * Sets the locked flag.
   *
   * @param bool $locked
   *   TRUE to lock the dictionary type.
   *
   * @return $this
   */
  public function setLocked(bool $locked): static;

}
