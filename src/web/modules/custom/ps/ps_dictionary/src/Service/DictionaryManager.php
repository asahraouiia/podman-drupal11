<?php

declare(strict_types=1);

namespace Drupal\ps_dictionary\Service;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\Logger\LoggerChannelInterface;
use Drupal\ps_dictionary\Entity\DictionaryEntryInterface;

/**
 * Dictionary manager service.
 *
 * Provides centralized management of business dictionaries including:
 * - Code validation with caching
 * - Label retrieval with translation support
 * - Options generation for form elements
 * - Cache management and invalidation.
 *
 * Performance: O(1) after first load per dictionary type (cached).
 *
 * @see \Drupal\ps_dictionary\Service\DictionaryManagerInterface
 * @see docs/specs/03-ps-dictionary.md#dictionary-manager
 */
final class DictionaryManager implements DictionaryManagerInterface {

  /**
   * Cache of loaded entries by type.
   *
   * @var array<string, \Drupal\ps_dictionary\Entity\DictionaryEntryInterface[]>
   */
  private array $entriesCache = [];

  /**
   * The logger channel.
   */
  private readonly LoggerChannelInterface $logger;

  /**
   * Constructs a DictionaryManager.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The entity type manager.
   * @param \Drupal\Core\Cache\CacheBackendInterface $cache
   *   The cache backend.
   * @param \Drupal\Core\Logger\LoggerChannelFactoryInterface $loggerFactory
   *   The logger factory.
   */
  public function __construct(
    private readonly EntityTypeManagerInterface $entityTypeManager,
    private readonly CacheBackendInterface $cache,
    LoggerChannelFactoryInterface $loggerFactory,
  ) {
    $this->logger = $loggerFactory->get('ps_dictionary');
  }

  /**
   * {@inheritdoc}
   */
  public function isValid(string $type, string $code): bool {
    $entry = $this->getEntry($type, $code);
    return $entry !== NULL && $entry->isActive();
  }

  /**
   * {@inheritdoc}
   */
  public function getLabel(string $type, string $code, ?string $langcode = NULL): ?string {
    $entry = $this->getEntry($type, $code);

    // Only return label for active entries.
    if ($entry && !$entry->isActive()) {
      return NULL;
    }

    return $entry?->getLabel();
  }

  /**
   * {@inheritdoc}
   */
  public function getOptions(string $type, bool $activeOnly = TRUE): array {
    $entries = $this->getEntries($type, $activeOnly);
    $options = [];

    foreach ($entries as $entry) {
      $options[$entry->getCode()] = $entry->getLabel();
    }

    return $options;
  }

  /**
   * {@inheritdoc}
   */
  public function getEntry(string $type, string $code): ?DictionaryEntryInterface {
    $entries = $this->loadEntries($type);
    $id = $type . '_' . strtolower($code);

    return $entries[$id] ?? NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function getEntries(string $type, bool $activeOnly = TRUE): array {
    $entries = $this->loadEntries($type);

    if ($activeOnly) {
      $entries = array_filter($entries, fn($entry) => $entry->isActive());
    }

    // Sort by weight, then label.
    uasort($entries, function (DictionaryEntryInterface $a, DictionaryEntryInterface $b) {
      $weightCompare = $a->getWeight() <=> $b->getWeight();
      return $weightCompare !== 0 ? $weightCompare : strcmp($a->getLabel(), $b->getLabel());
    });

    return $entries;
  }

  /**
   * {@inheritdoc}
   */
  public function isDeprecated(string $type, string $code): bool {
    $entry = $this->getEntry($type, $code);
    return $entry?->isDeprecated() ?? FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function getMetadata(string $type, string $code): array {
    $entry = $this->getEntry($type, $code);
    return $entry?->getMetadata() ?? [];
  }

  /**
   * {@inheritdoc}
   */
  public function clearCache(?string $type = NULL): void {
    if ($type === NULL) {
      $this->entriesCache = [];
      $this->cache->deleteAll();
    }
    else {
      unset($this->entriesCache[$type]);
      $this->cache->delete('ps_dictionary:entries:' . $type);
    }
  }

  /**
   * Loads all entries for a dictionary type with caching.
   *
   * @param string $type
   *   Dictionary type ID.
   *
   * @return \Drupal\ps_dictionary\Entity\DictionaryEntryInterface[]
   *   Array of entries keyed by entry ID.
   */
  private function loadEntries(string $type): array {
    // Check runtime cache first.
    if (isset($this->entriesCache[$type])) {
      return $this->entriesCache[$type];
    }

    // Check persistent cache.
    $cid = 'ps_dictionary:entries:' . $type;
    $cached = $this->cache->get($cid);

    if ($cached !== FALSE) {
      $this->entriesCache[$type] = $cached->data;
      return $cached->data;
    }

    // Load from storage.
    try {
      $storage = $this->entityTypeManager->getStorage('ps_dictionary_entry');
      $ids = $storage->getQuery()
        ->condition('dictionary_type', $type)
        ->accessCheck(FALSE)
        ->execute();

      /** @var array<string, \Drupal\ps_dictionary\Entity\DictionaryEntryInterface> $entries */
      $entries = $storage->loadMultiple($ids);

      // Cache for 1 hour with invalidation tag.
      $this->cache->set($cid, $entries, time() + 3600, ['ps_dictionary:' . $type]);
      $this->entriesCache[$type] = $entries;

      return $entries;
    }
    catch (\Exception $e) {
      $this->logger->error('Failed to load dictionary entries for type @type: @message', [
        '@type' => $type,
        '@message' => $e->getMessage(),
      ]);

      return [];
    }
  }

}
