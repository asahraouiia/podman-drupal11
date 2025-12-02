<?php

declare(strict_types=1);

namespace Drupal\Tests\ps_dictionary\Kernel;

use Drupal\KernelTests\KernelTestBase;
use Drupal\ps_dictionary\Entity\DictionaryEntry;

/**
 * Kernel tests for DictionaryManager service with real entities.
 *
 * @group ps_dictionary
 * @coversDefaultClass \Drupal\ps_dictionary\Service\DictionaryManager
 */
class DictionaryManagerKernelTest extends KernelTestBase {

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
   * Dictionary manager service.
   *
   * @var \Drupal\ps_dictionary\Service\DictionaryManagerInterface
   */
  protected $dictionaryManager;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->installConfig(['ps_dictionary']);
    $this->dictionaryManager = \Drupal::service('ps_dictionary.manager');

    // Create test entries.
    DictionaryEntry::create([
      'id' => 'test_active',
      'dictionary_type' => 'test',
      'code' => 'ACTIVE',
      'label' => 'Active Entry',
      'weight' => 0,
      'status' => TRUE,
      'deprecated' => FALSE,
    ])->save();

    DictionaryEntry::create([
      'id' => 'test_inactive',
      'dictionary_type' => 'test',
      'code' => 'INACTIVE',
      'label' => 'Inactive Entry',
      'weight' => 10,
      'status' => FALSE,
      'deprecated' => FALSE,
    ])->save();

    DictionaryEntry::create([
      'id' => 'test_deprecated',
      'dictionary_type' => 'test',
      'code' => 'DEPRECATED',
      'label' => 'Deprecated Entry',
      'weight' => 20,
      'status' => TRUE,
      'deprecated' => TRUE,
    ])->save();
  }

  /**
   * Tests isValid() method.
   *
   * @covers ::isValid
   */
  public function testIsValid(): void {
    // Active entry is valid.
    $this->assertTrue($this->dictionaryManager->isValid('test', 'ACTIVE'));

    // Inactive entry is not valid.
    $this->assertFalse($this->dictionaryManager->isValid('test', 'INACTIVE'));

    // Deprecated entry is valid (still usable).
    $this->assertTrue($this->dictionaryManager->isValid('test', 'DEPRECATED'));

    // Non-existent entry is not valid.
    $this->assertFalse($this->dictionaryManager->isValid('test', 'NONEXISTENT'));
  }

  /**
   * Tests getLabel() method.
   *
   * @covers ::getLabel
   */
  public function testGetLabel(): void {
    $this->assertEquals('Active Entry', $this->dictionaryManager->getLabel('test', 'ACTIVE'));
    $this->assertNull($this->dictionaryManager->getLabel('test', 'INACTIVE'));
    $this->assertEquals('Deprecated Entry', $this->dictionaryManager->getLabel('test', 'DEPRECATED'));
    $this->assertNull($this->dictionaryManager->getLabel('test', 'NONEXISTENT'));
  }

  /**
   * Tests getOptions() method.
   *
   * @covers ::getOptions
   */
  public function testGetOptions(): void {
    // Active only (default).
    $options = $this->dictionaryManager->getOptions('test');
    $this->assertCount(2, $options);
    $this->assertArrayHasKey('ACTIVE', $options);
    $this->assertArrayHasKey('DEPRECATED', $options);
    $this->assertArrayNotHasKey('INACTIVE', $options);

    // Include inactive.
    $all_options = $this->dictionaryManager->getOptions('test', FALSE);
    $this->assertCount(3, $all_options);
    $this->assertArrayHasKey('ACTIVE', $all_options);
    $this->assertArrayHasKey('INACTIVE', $all_options);
    $this->assertArrayHasKey('DEPRECATED', $all_options);
  }

  /**
   * Tests getEntry() method.
   *
   * @covers ::getEntry
   */
  public function testGetEntry(): void {
    $entry = $this->dictionaryManager->getEntry('test', 'ACTIVE');
    $this->assertNotNull($entry);
    $this->assertEquals('ACTIVE', $entry->getCode());
    $this->assertEquals('Active Entry', $entry->getLabel());

    $nonexistent = $this->dictionaryManager->getEntry('test', 'NONEXISTENT');
    $this->assertNull($nonexistent);
  }

  /**
   * Tests getEntries() method.
   *
   * @covers ::getEntries
   */
  public function testGetEntries(): void {
    // Active only.
    $entries = $this->dictionaryManager->getEntries('test');
    $this->assertCount(2, $entries);

    // All entries.
    $all_entries = $this->dictionaryManager->getEntries('test', FALSE);
    $this->assertCount(3, $all_entries);
  }

  /**
   * Tests isDeprecated() method.
   *
   * @covers ::isDeprecated
   */
  public function testIsDeprecated(): void {
    $this->assertFalse($this->dictionaryManager->isDeprecated('test', 'ACTIVE'));
    $this->assertTrue($this->dictionaryManager->isDeprecated('test', 'DEPRECATED'));
    $this->assertFalse($this->dictionaryManager->isDeprecated('test', 'NONEXISTENT'));
  }

  /**
   * Tests cache functionality.
   *
   * @covers ::clearCache
   */
  public function testCacheFunctionality(): void {
    // First call loads from database.
    $label1 = $this->dictionaryManager->getLabel('test', 'ACTIVE');
    $this->assertEquals('Active Entry', $label1);

    // Update entry directly.
    $entry = DictionaryEntry::load('test_active');
    $entry->setLabel('Updated Label');
    $entry->save();

    // Without cache clear, still returns old value.
    $label2 = $this->dictionaryManager->getLabel('test', 'ACTIVE');
    $this->assertEquals('Active Entry', $label2);

    // After cache clear, returns new value.
    $this->dictionaryManager->clearCache('test');
    $label3 = $this->dictionaryManager->getLabel('test', 'ACTIVE');
    $this->assertEquals('Updated Label', $label3);
  }

  /**
   * Tests case-insensitive code lookup.
   *
   * @covers ::getLabel
   * @covers ::isValid
   */
  public function testCaseInsensitiveCodeLookup(): void {
    // Test different case variations.
    $this->assertTrue($this->dictionaryManager->isValid('test', 'active'));
    $this->assertTrue($this->dictionaryManager->isValid('test', 'Active'));
    $this->assertTrue($this->dictionaryManager->isValid('test', 'ACTIVE'));

    $this->assertEquals('Active Entry', $this->dictionaryManager->getLabel('test', 'active'));
    $this->assertEquals('Active Entry', $this->dictionaryManager->getLabel('test', 'Active'));
  }

}
