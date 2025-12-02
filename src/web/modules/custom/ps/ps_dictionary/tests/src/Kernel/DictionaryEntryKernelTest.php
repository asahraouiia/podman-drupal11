<?php

declare(strict_types=1);

namespace Drupal\Tests\ps_dictionary\Kernel;

use Drupal\KernelTests\KernelTestBase;
use Drupal\ps_dictionary\Entity\DictionaryEntry;

/**
 * Kernel tests for DictionaryEntry entity.
 *
 * @group ps_dictionary
 * @coversDefaultClass \Drupal\ps_dictionary\Entity\DictionaryEntry
 */
class DictionaryEntryKernelTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'system',
    'user',
    'ps',
    'ps_dictionary',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->installConfig(['ps_dictionary']);
  }

  /**
   * Tests dictionary entry CRUD operations.
   *
   * @covers ::create
   * @covers ::save
   * @covers ::load
   * @covers ::delete
   */
  public function testDictionaryEntryCrud(): void {
    // Create entry.
    $entry = DictionaryEntry::create([
      'id' => 'test_type_code1',
      'dictionary_type' => 'test_type',
      'code' => 'CODE1',
      'label' => 'Test Code 1',
      'description' => 'Test description',
      'weight' => 10,
      'status' => TRUE,
      'deprecated' => FALSE,
    ]);

    $this->assertEquals('test_type_code1', $entry->id());
    $this->assertEquals('test_type', $entry->getDictionaryType());
    $this->assertEquals('CODE1', $entry->getCode());
    $this->assertEquals('Test Code 1', $entry->getLabel());
    $this->assertEquals('Test description', $entry->getDescription());
    $this->assertEquals(10, $entry->getWeight());
    $this->assertTrue($entry->isActive());
    $this->assertFalse($entry->isDeprecated());

    // Save entry.
    $status = $entry->save();
    $this->assertEquals(SAVED_NEW, $status);

    // Load entry.
    $loaded_entry = DictionaryEntry::load('test_type_code1');
    $this->assertNotNull($loaded_entry);
    $this->assertEquals('CODE1', $loaded_entry->getCode());
    $this->assertEquals('Test Code 1', $loaded_entry->getLabel());

    // Update entry.
    $loaded_entry->setLabel('Updated Label');
    $loaded_entry->setWeight(20);
    $status = $loaded_entry->save();
    $this->assertEquals(SAVED_UPDATED, $status);

    // Reload and verify.
    $updated_entry = DictionaryEntry::load('test_type_code1');
    $this->assertEquals('Updated Label', $updated_entry->getLabel());
    $this->assertEquals(20, $updated_entry->getWeight());

    // Delete entry.
    $updated_entry->delete();
    $deleted_entry = DictionaryEntry::load('test_type_code1');
    $this->assertNull($deleted_entry);
  }

  /**
   * Tests metadata handling.
   *
   * @covers ::getMetadata
   * @covers ::setMetadata
   */
  public function testMetadataHandling(): void {
    $entry = DictionaryEntry::create([
      'id' => 'test_type_meta',
      'dictionary_type' => 'test_type',
      'code' => 'META',
      'label' => 'Metadata Test',
      'metadata' => [
        'symbol' => '€',
        'iso_code' => 'EUR',
        'decimal_places' => 2,
      ],
    ]);

    $metadata = $entry->getMetadata();
    $this->assertEquals('€', $metadata['symbol']);
    $this->assertEquals('EUR', $metadata['iso_code']);
    $this->assertEquals(2, $metadata['decimal_places']);

    // Update metadata.
    $entry->setMetadata([
      'symbol' => '$',
      'iso_code' => 'USD',
    ]);

    $updated_metadata = $entry->getMetadata();
    $this->assertEquals('$', $updated_metadata['symbol']);
    $this->assertEquals('USD', $updated_metadata['iso_code']);
    $this->assertArrayNotHasKey('decimal_places', $updated_metadata);
  }

  /**
   * Tests status flags.
   *
   * @covers ::isActive
   * @covers ::setStatus
   * @covers ::isDeprecated
   * @covers ::setDeprecated
   */
  public function testStatusFlags(): void {
    $entry = DictionaryEntry::create([
      'id' => 'test_type_status',
      'dictionary_type' => 'test_type',
      'code' => 'STATUS',
      'label' => 'Status Test',
      'status' => TRUE,
      'deprecated' => FALSE,
    ]);

    $this->assertTrue($entry->isActive());
    $this->assertFalse($entry->isDeprecated());

    // Deactivate.
    $entry->setStatus(FALSE);
    $this->assertFalse($entry->isActive());

    // Mark as deprecated.
    $entry->setDeprecated(TRUE);
    $this->assertTrue($entry->isDeprecated());
  }

  /**
   * Tests weight-based sorting.
   *
   * @covers ::getWeight
   * @covers ::setWeight
   */
  public function testWeightSorting(): void {
    // Create entries with different weights.
    $entry1 = DictionaryEntry::create([
      'id' => 'test_type_weight1',
      'dictionary_type' => 'test_type',
      'code' => 'WEIGHT1',
      'label' => 'Weight 30',
      'weight' => 30,
    ]);
    $entry1->save();

    $entry2 = DictionaryEntry::create([
      'id' => 'test_type_weight2',
      'dictionary_type' => 'test_type',
      'code' => 'WEIGHT2',
      'label' => 'Weight 10',
      'weight' => 10,
    ]);
    $entry2->save();

    $entry3 = DictionaryEntry::create([
      'id' => 'test_type_weight3',
      'dictionary_type' => 'test_type',
      'code' => 'WEIGHT3',
      'label' => 'Weight 20',
      'weight' => 20,
    ]);
    $entry3->save();

    // Load all entries and check order.
    $storage = \Drupal::entityTypeManager()->getStorage('ps_dictionary_entry');
    $query = $storage->getQuery()
      ->condition('dictionary_type', 'test_type')
      ->sort('weight', 'ASC')
      ->accessCheck(FALSE);
    $ids = $query->execute();

    $expected_order = [
      'test_type_weight2',
      'test_type_weight3',
      'test_type_weight1',
    ];

    $this->assertEquals($expected_order, array_values($ids));
  }

}
