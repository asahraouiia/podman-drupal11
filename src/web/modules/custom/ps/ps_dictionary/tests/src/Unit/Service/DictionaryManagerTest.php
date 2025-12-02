<?php

declare(strict_types=1);

namespace Drupal\Tests\ps_dictionary\Unit\Service;

use PHPUnit\Framework\TestCase;
use Drupal\ps_dictionary\Service\DictionaryManager;
use Drupal\ps_dictionary\Entity\DictionaryEntryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\Query\QueryInterface;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\Logger\LoggerChannelInterface;

/**
 * Tests DictionaryManager service.
 *
 * @coversDefaultClass \Drupal\ps_dictionary\Service\DictionaryManager
 * @group ps_dictionary
 */
class DictionaryManagerTest extends TestCase {

  /**
   * The dictionary manager under test.
   */
  protected DictionaryManager $dictionaryManager;

  /**
   * Mock entity type manager.
   */
  protected EntityTypeManagerInterface $entityTypeManager;

  /**
   * Mock cache backend.
   */
  protected CacheBackendInterface $cache;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->entityTypeManager = $this->createMock(EntityTypeManagerInterface::class);
    $this->cache = $this->createMock(CacheBackendInterface::class);
    $loggerFactory = $this->createMock(LoggerChannelFactoryInterface::class);
    $logger = $this->createMock(LoggerChannelInterface::class);

    $loggerFactory->method('get')
      ->willReturn($logger);

    $this->dictionaryManager = new DictionaryManager(
      $this->entityTypeManager,
      $this->cache,
      $loggerFactory
    );
  }

  /**
   * @covers ::isValid
   */
  public function testIsValid(): void {
    $entry = $this->createMock(DictionaryEntryInterface::class);
    $entry->method('isActive')->willReturn(TRUE);
    $entry->method('getCode')->willReturn('SALE');

    $this->setupMockStorage(['property_type_sale' => $entry]);

    $result = $this->dictionaryManager->isValid('property_type', 'SALE');
    $this->assertTrue($result);
  }

  /**
   * @covers ::getLabel
   */
  public function testGetLabel(): void {
    $entry = $this->createMock(DictionaryEntryInterface::class);
    $entry->method('getLabel')->willReturn('For Sale');
    $entry->method('getCode')->willReturn('SALE');
    $entry->method('isActive')->willReturn(TRUE);

    $this->setupMockStorage(['property_type_sale' => $entry]);

    $label = $this->dictionaryManager->getLabel('property_type', 'SALE');
    $this->assertEquals('For Sale', $label);
  }

  /**
   * @covers ::getOptions
   */
  public function testGetOptions(): void {
    $entry1 = $this->createMock(DictionaryEntryInterface::class);
    $entry1->method('getCode')->willReturn('SALE');
    $entry1->method('getLabel')->willReturn('For Sale');
    $entry1->method('isActive')->willReturn(TRUE);
    $entry1->method('getWeight')->willReturn(0);

    $entry2 = $this->createMock(DictionaryEntryInterface::class);
    $entry2->method('getCode')->willReturn('RENT');
    $entry2->method('getLabel')->willReturn('For Rent');
    $entry2->method('isActive')->willReturn(TRUE);
    $entry2->method('getWeight')->willReturn(1);

    $this->setupMockStorage([
      'property_type_sale' => $entry1,
      'property_type_rent' => $entry2,
    ]);

    $options = $this->dictionaryManager->getOptions('property_type');
    $this->assertEquals(['SALE' => 'For Sale', 'RENT' => 'For Rent'], $options);
  }

  /**
   * Tests getEntry method.
   *
   * @covers ::getEntry
   */
  public function testGetEntry(): void {
    $entry = $this->createMock(DictionaryEntryInterface::class);
    $entry->method('getCode')->willReturn('EUR');
    $entry->method('getLabel')->willReturn('Euro');

    $this->setupMockStorage(['currency_eur' => $entry]);

    $result = $this->dictionaryManager->getEntry('currency', 'EUR');
    $this->assertSame($entry, $result);
  }

  /**
   * Tests isDeprecated method.
   *
   * @covers ::isDeprecated
   */
  public function testIsDeprecated(): void {
    $entry = $this->createMock(DictionaryEntryInterface::class);
    $entry->method('isDeprecated')->willReturn(TRUE);

    $this->setupMockStorage(['property_type_sale' => $entry]);

    $this->assertTrue($this->dictionaryManager->isDeprecated('property_type', 'SALE'));
  }

  /**
   * Tests getMetadata method.
   *
   * @covers ::getMetadata
   */
  public function testGetMetadata(): void {
    $metadata = ['symbol' => '€', 'iso_code' => 'EUR'];
    $entry = $this->createMock(DictionaryEntryInterface::class);
    $entry->method('getMetadata')->willReturn($metadata);

    $this->setupMockStorage(['currency_eur' => $entry]);

    $result = $this->dictionaryManager->getMetadata('currency', 'EUR');
    $this->assertEquals($metadata, $result);
  }

  /**
   * Tests clearCache method.
   *
   * @covers ::clearCache
   */
  public function testClearCache(): void {
    $this->cache->expects($this->once())
      ->method('delete')
      ->with('ps_dictionary:entries:currency');

    $this->dictionaryManager->clearCache('currency');
  }

  /**
   * Sets up mock storage for testing.
   *
   * @param array $entries
   *   Array of mock entry entities.
   */
  protected function setupMockStorage(array $entries): void {
    $storage = $this->createMock(EntityStorageInterface::class);
    $query = $this->createMock(QueryInterface::class);

    $storage->method('getQuery')->willReturn($query);
    $query->method('condition')->willReturnSelf();
    $query->method('accessCheck')->willReturnSelf();
    $query->method('execute')->willReturn(array_keys($entries));
    $storage->method('loadMultiple')->willReturn($entries);

    $this->entityTypeManager->method('getStorage')
      ->with('ps_dictionary_entry')
      ->willReturn($storage);

    $this->cache->method('get')->willReturn(FALSE);
  }

}
