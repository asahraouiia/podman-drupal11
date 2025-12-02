<?php

declare(strict_types=1);

namespace Drupal\ps_dictionary\Entity;

use Drupal\Core\Config\Entity\ConfigEntityInterface;

/**
 * Interface for Dictionary Entry configuration entities.
 *
 * @see \Drupal\ps_dictionary\Entity\DictionaryEntry
 */
interface DictionaryEntryInterface extends ConfigEntityInterface {

  /**
   * Gets the dictionary type ID.
   *
   * @return string
   *   The dictionary type ID.
   */
  public function getDictionaryType(): string;

  /**
   * Sets the dictionary type ID.
   *
   * @param string $type
   *   The dictionary type ID.
   *
   * @return $this
   */
  public function setDictionaryType(string $type): static;

  /**
   * Gets the entry code.
   *
   * @return string
   *   The code.
   */
  public function getCode(): string;

  /**
   * Sets the entry code.
   *
   * @param string $code
   *   The code.
   *
   * @return $this
   */
  public function setCode(string $code): static;

  /**
   * Gets the entry label.
   *
   * @return string
   *   The label.
   */
  public function getLabel(): string;

  /**
   * Sets the entry label.
   *
   * @param string $label
   *   The label.
   *
   * @return $this
   */
  public function setLabel(string $label): static;

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
   * Gets the weight.
   *
   * @return int
   *   The weight.
   */
  public function getWeight(): int;

  /**
   * Sets the weight.
   *
   * @param int $weight
   *   The weight.
   *
   * @return $this
   */
  public function setWeight(int $weight): static;

  /**
   * Checks if the entry is active.
   *
   * @return bool
   *   TRUE if active.
   */
  public function isActive(): bool;

  /**
   * Sets the active status.
   *
   * @param mixed $status
   *   Status value.
   *
   * @return $this
   */
  public function setStatus($status): static;

  /**
   * Checks if the entry is deprecated.
   *
   * @return bool
   *   TRUE if deprecated.
   */
  public function isDeprecated(): bool;

  /**
   * Sets the deprecated flag.
   *
   * @param bool $deprecated
   *   TRUE to deprecate.
   *
   * @return $this
   */
  public function setDeprecated(bool $deprecated): static;

  /**
   * Gets the metadata array.
   *
   * @return array<string, mixed>
   *   The metadata.
   */
  public function getMetadata(): array;

  /**
   * Sets the metadata array.
   *
   * @param array<string, mixed> $metadata
   *   The metadata.
   *
   * @return $this
   */
  public function setMetadata(array $metadata): static;

}
