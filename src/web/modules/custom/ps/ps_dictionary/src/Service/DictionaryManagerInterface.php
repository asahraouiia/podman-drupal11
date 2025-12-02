<?php

declare(strict_types=1);

namespace Drupal\ps_dictionary\Service;

use Drupal\ps_dictionary\Entity\DictionaryEntryInterface;

/**
 * Interface for the Dictionary Manager service.
 *
 * Provides centralized management of business dictionaries including:
 * - Code validation with caching
 * - Label retrieval with translation support
 * - Options generation for form elements
 * - Cache management and invalidation.
 *
 * Performance: O(1) after first load per dictionary type (cached).
 *
 * @see \Drupal\ps_dictionary\Service\DictionaryManager
 * @see docs/specs/03-ps-dictionary.md#dictionary-manager
 */
interface DictionaryManagerInterface {

  /**
   * Checks if a code is valid for a dictionary type.
   *
   * @param string $type
   *   Dictionary type ID (e.g., 'property_type').
   * @param string $code
   *   Code to validate (e.g., 'SALE').
   *
   * @return bool
   *   TRUE if the code exists and is active.
   */
  public function isValid(string $type, string $code): bool;

  /**
   * Gets the label for a dictionary entry.
   *
   * @param string $type
   *   Dictionary type ID.
   * @param string $code
   *   Entry code.
   * @param string|null $langcode
   *   Language code (NULL = current language).
   *
   * @return string|null
   *   The label or NULL if not found.
   */
  public function getLabel(string $type, string $code, ?string $langcode = NULL): ?string;

  /**
   * Gets all entries for a type as options array.
   *
   * Suitable for use in form select/checkboxes elements.
   *
   * @param string $type
   *   Dictionary type ID.
   * @param bool $activeOnly
   *   Filter to active entries only.
   *
   * @return array<string, string>
   *   Options array [code => label], sorted by weight then label.
   */
  public function getOptions(string $type, bool $activeOnly = TRUE): array;

  /**
   * Gets a dictionary entry entity.
   *
   * @param string $type
   *   Dictionary type ID.
   * @param string $code
   *   Entry code.
   *
   * @return \Drupal\ps_dictionary\Entity\DictionaryEntryInterface|null
   *   The entry entity or NULL if not found.
   */
  public function getEntry(string $type, string $code): ?DictionaryEntryInterface;

  /**
   * Gets all entries for a dictionary type.
   *
   * @param string $type
   *   Dictionary type ID.
   * @param bool $activeOnly
   *   Filter to active entries only.
   *
   * @return \Drupal\ps_dictionary\Entity\DictionaryEntryInterface[]
   *   Array of entry entities, sorted by weight then label.
   */
  public function getEntries(string $type, bool $activeOnly = TRUE): array;

  /**
   * Checks if a code is deprecated.
   *
   * @param string $type
   *   Dictionary type ID.
   * @param string $code
   *   Entry code.
   *
   * @return bool
   *   TRUE if the entry exists and is marked deprecated.
   */
  public function isDeprecated(string $type, string $code): bool;

  /**
   * Gets entry metadata.
   *
   * @param string $type
   *   Dictionary type ID.
   * @param string $code
   *   Entry code.
   *
   * @return array<string, mixed>
   *   Metadata array or empty array if not found.
   */
  public function getMetadata(string $type, string $code): array;

  /**
   * Clears the internal cache for a dictionary type.
   *
   * @param string|null $type
   *   Dictionary type ID, or NULL to clear all.
   */
  public function clearCache(?string $type = NULL): void;

}
