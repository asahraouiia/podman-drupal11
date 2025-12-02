<?php

declare(strict_types=1);

namespace Drupal\ps_dictionary\Commands;

use Drupal\ps_dictionary\Service\DictionaryManagerInterface;
use Drush\Attributes as CLI;
use Drush\Commands\DrushCommands;
use Symfony\Component\Yaml\Yaml;
use Drupal\Core\Entity\EntityTypeManagerInterface;

/**
 * Drush commands for ps_dictionary module.
 *
 * Provides commands for exporting and importing dictionary data.
 */
final class DictionaryCommands extends DrushCommands {

  /**
   * Constructs a DictionaryCommands object.
   *
   * @param \Drupal\ps_dictionary\Service\DictionaryManagerInterface $dictionaryManager
   *   The dictionary manager service.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The entity type manager.
   */
  public function __construct(
    private readonly DictionaryManagerInterface $dictionaryManager,
    private readonly EntityTypeManagerInterface $entityTypeManager,
  ) {
    parent::__construct();
  }

  /**
   * List all dictionary types.
   */
  #[CLI\Command(name: 'ps:dictionary-list', aliases: ['ps-dict-list'])]
  #[CLI\Usage(name: 'drush ps:dictionary-list', description: 'List all dictionary types')]
  public function listTypes(): void {
    $storage = $this->entityTypeManager->getStorage('ps_dictionary_type');
    $types = $storage->loadMultiple();

    if (empty($types)) {
      $this->io()->warning('No dictionary types found.');
      return;
    }

    $rows = [];
    foreach ($types as $type) {
      $entries = $this->dictionaryManager->getEntries($type->id(), FALSE);
      $rows[] = [
        $type->id(),
        $type->label(),
        count($entries),
      ];
    }

    $this->io()->table(['ID', 'Label', 'Entries'], $rows);
  }

  /**
   * Show entries for a dictionary type.
   *
   * @param string $type
   *   Dictionary type ID.
   */
  #[CLI\Command(name: 'ps:dictionary-show', aliases: ['ps-dict-show'])]
  #[CLI\Argument(name: 'type', description: 'Dictionary type ID')]
  #[CLI\Usage(name: 'drush ps:dictionary-show property_type', description: 'Show entries for property_type')]
  public function showEntries(string $type): void {
    $entries = $this->dictionaryManager->getEntries($type, FALSE);

    if (empty($entries)) {
      $this->io()->warning("No entries found for type: {$type}");
      return;
    }

    $rows = [];
    foreach ($entries as $entry) {
      $rows[] = [
        $entry->getCode(),
        $entry->getLabel(),
        $entry->isActive() ? 'Active' : 'Inactive',
        $entry->isDeprecated() ? 'Yes' : 'No',
        $entry->getWeight(),
      ];
    }

    $this->io()->title("Dictionary: {$type}");
    $this->io()->table(['Code', 'Label', 'Status', 'Deprecated', 'Weight'], $rows);
  }

  /**
   * Export dictionary entries to YAML.
   *
   * @param string $type
   *   Dictionary type ID.
   * @param array<string, mixed> $options
   *   Command options.
   */
  #[CLI\Command(name: 'ps:dictionary-export', aliases: ['ps-dict-export'])]
  #[CLI\Argument(name: 'type', description: 'Dictionary type ID')]
  #[CLI\Option(name: 'format', description: 'Output format (yaml|json)')]
  #[CLI\Usage(name: 'drush ps:dictionary-export property_type', description: 'Export property_type dictionary')]
  #[CLI\Usage(name: 'drush ps:dictionary-export property_type --format=json', description: 'Export as JSON')]
  public function export(string $type, array $options = ['format' => 'yaml']): void {
    $entries = $this->dictionaryManager->getEntries($type, FALSE);

    if (empty($entries)) {
      $this->io()->error("No entries found for type: {$type}");
      return;
    }

    $data = [
      'dictionary_type' => $type,
      'entries' => [],
    ];

    foreach ($entries as $entry) {
      $data['entries'][] = [
        'code' => $entry->getCode(),
        'label' => $entry->getLabel(),
        'description' => $entry->getDescription(),
        'weight' => $entry->getWeight(),
        'status' => $entry->isActive(),
        'deprecated' => $entry->isDeprecated(),
        'metadata' => $entry->getMetadata(),
      ];
    }

    if ($options['format'] === 'json') {
      $this->io()->write(json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    }
    else {
      $this->io()->write(Yaml::dump($data, 4, 2));
    }
  }

  /**
   * Clear dictionary cache.
   *
   * @param string|null $type
   *   Dictionary type ID or NULL to clear all.
   */
  #[CLI\Command(name: 'ps:dictionary-cache-clear', aliases: ['ps-dict-cc'])]
  #[CLI\Argument(name: 'type', description: 'Dictionary type ID (optional)')]
  #[CLI\Usage(name: 'drush ps:dictionary-cache-clear', description: 'Clear all dictionary caches')]
  #[CLI\Usage(name: 'drush ps:dictionary-cache-clear property_type', description: 'Clear cache for property_type')]
  public function clearCache(?string $type = NULL): void {
    $this->dictionaryManager->clearCache($type);

    if ($type) {
      $this->io()->success("Cleared cache for dictionary type: {$type}");
    }
    else {
      $this->io()->success('Cleared all dictionary caches');
    }
  }

}
