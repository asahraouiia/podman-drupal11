<?php

declare(strict_types=1);

namespace Drupal\Tests\ps_division\Unit\Service;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\Query\QueryInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\Logger\LoggerChannelInterface;
use Drupal\ps_dictionary\Service\DictionaryManagerInterface;
use Drupal\ps_division\Entity\DivisionInterface;
use Drupal\ps_division\Service\DivisionManager;
use Drupal\Tests\UnitTestCase;

/**
 * Unit tests for DivisionManager service.
 *
 * @coversDefaultClass \Drupal\ps_division\Service\DivisionManager
 * @group ps_division
 */
final class DivisionManagerTest extends UnitTestCase {

  /**
   * The mocked entity type manager.
   */
  private EntityTypeManagerInterface $entityTypeManager;

  /**
   * The mocked dictionary manager.
   */
  private DictionaryManagerInterface $dictionaryManager;

  /**
   * The mocked cache backend.
   */
  private CacheBackendInterface $cache;

  /**
   * The mocked logger factory.
   */
  private LoggerChannelFactoryInterface $loggerFactory;

  /**
   * The division manager under test.
   */
  private DivisionManager $divisionManager;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->entityTypeManager = $this->createMock(EntityTypeManagerInterface::class);
    $this->dictionaryManager = $this->createMock(DictionaryManagerInterface::class);
    $this->cache = $this->createMock(CacheBackendInterface::class);
    $this->loggerFactory = $this->createMock(LoggerChannelFactoryInterface::class);

    $logger = $this->createMock(LoggerChannelInterface::class);
    $this->loggerFactory->method('get')->willReturn($logger);

    $this->divisionManager = new DivisionManager(
      $this->entityTypeManager,
      $this->dictionaryManager,
      $this->cache,
      $this->loggerFactory,
    );
  }

  /**
   * @covers ::getByParent
   */
  public function testGetByParentReturnsEmptyArrayWhenNoResults(): void {
    $storage = $this->createMock(EntityStorageInterface::class);
    $query = $this->createMock(QueryInterface::class);

    $this->entityTypeManager
      ->method('getStorage')
      ->with('ps_division')
      ->willReturn($storage);

    $storage
      ->method('getQuery')
      ->willReturn($query);

    $query
      ->method('condition')
      ->with('entity_id', 1)
      ->willReturnSelf();

    $query
      ->method('accessCheck')
      ->with(FALSE)
      ->willReturnSelf();

    $query
      ->method('execute')
      ->willReturn([]);

    $storage
      ->method('loadMultiple')
      ->with([])
      ->willReturn([]);

    $this->cache
      ->expects($this->once())
      ->method('get')
      ->willReturn(FALSE);

    $this->cache
      ->expects($this->once())
      ->method('set');

    $result = $this->divisionManager->getByParent(1);
    $this->assertSame([], $result);
  }

  /**
   * @covers ::getByParent
   */
  public function testGetByParentReturnsCachedResult(): void {
    $divisions = ['division_1', 'division_2'];
    $cacheData = (object) ['data' => $divisions];

    $this->cache
      ->expects($this->once())
      ->method('get')
      ->with('ps_division:by_parent:1')
      ->willReturn($cacheData);

    $result = $this->divisionManager->getByParent(1);
    $this->assertSame($divisions, $result);
  }

  /**
   * @covers ::calculateTotalSurface
   */
  public function testCalculateTotalSurfaceReturnsZeroForNoDivisions(): void {
    $this->cache
      ->method('get')
      ->willReturn(FALSE);

    $storage = $this->createMock(EntityStorageInterface::class);
    $query = $this->createMock(QueryInterface::class);

    $this->entityTypeManager
      ->method('getStorage')
      ->willReturn($storage);

    $storage
      ->method('getQuery')
      ->willReturn($query);

    $query->method('condition')->willReturnSelf();
    $query->method('accessCheck')->willReturnSelf();
    $query->method('execute')->willReturn([]);
    $storage->method('loadMultiple')->willReturn([]);

    $this->cache->method('set');

    $result = $this->divisionManager->calculateTotalSurface(1);
    $this->assertSame(0.0, $result);
  }

  /**
   * @covers ::validate
   */
  public function testValidateDetectsNegativeSurfaceValue(): void {
    $division = $this->createMock(DivisionInterface::class);
    $surfaceField = $this->createStub(\Drupal\Core\Field\FieldItemListInterface::class);
    $surfaceItem = $this->createStub(\Drupal\ps_division\Plugin\Field\FieldType\SurfaceItem::class);

    $surfaceItem->method('getValue')->willReturn(-10);
    $surfaceItem->method('getUnit')->willReturn('m2');
    $surfaceItem->method('getType')->willReturn(NULL);
    $surfaceItem->method('getNature')->willReturn(NULL);
    $surfaceItem->method('getQualification')->willReturn(NULL);

    $surfaceField->method('getIterator')->willReturn(new \ArrayIterator([$surfaceItem]));

    $typeField = $this->createStub(\Drupal\Core\Field\FieldItemListInterface::class);
    $typeField->value = NULL;

    $natureField = $this->createStub(\Drupal\Core\Field\FieldItemListInterface::class);
    $natureField->value = NULL;

    $division->method('get')->willReturnMap([
      ['surfaces', $surfaceField],
      ['type', $typeField],
      ['nature', $natureField],
    ]);

    $this->dictionaryManager->method('isValid')->willReturn(TRUE);

    $errors = $this->divisionManager->validate($division);
    $this->assertContains('Surface #0: value must be >= 0.', $errors);
  }

  /**
   * @covers ::validate
   */
  public function testValidateDetectsInvalidDictionaryCode(): void {
    $division = $this->createMock(DivisionInterface::class);
    $surfaceField = $this->createStub(\Drupal\Core\Field\FieldItemListInterface::class);
    $surfaceItem = $this->createStub(\Drupal\ps_division\Plugin\Field\FieldType\SurfaceItem::class);

    $surfaceItem->method('getValue')->willReturn(50.0);
    $surfaceItem->method('getUnit')->willReturn('invalid_unit');
    $surfaceItem->method('getType')->willReturn(NULL);
    $surfaceItem->method('getNature')->willReturn(NULL);
    $surfaceItem->method('getQualification')->willReturn(NULL);

    $surfaceField->method('getIterator')->willReturn(new \ArrayIterator([$surfaceItem]));

    $typeField = $this->createStub(\Drupal\Core\Field\FieldItemListInterface::class);
    $typeField->value = NULL;

    $natureField = $this->createStub(\Drupal\Core\Field\FieldItemListInterface::class);
    $natureField->value = NULL;

    $division->method('get')->willReturnMap([
      ['surfaces', $surfaceField],
      ['type', $typeField],
      ['nature', $natureField],
    ]);

    $this->dictionaryManager
      ->method('isValid')
      ->willReturnCallback(function ($dict, $code) {
        return $code !== 'invalid_unit';
      });

    $errors = $this->divisionManager->validate($division);
    $this->assertContains("Surface #0: invalid unit 'invalid_unit'.", $errors);
  }

  /**
   * @covers ::getSummary
   */
  public function testGetSummaryReturnsExpectedStructure(): void {
    $division = $this->createMock(DivisionInterface::class);
    $division->method('id')->willReturn(42);
    $division->method('getBuildingName')->willReturn('Test Building');
    $division->method('getLot')->willReturn('LOT-123');
    $division->method('getTotalSurface')->willReturn(75.50);

    $typeField = $this->createStub(\Drupal\Core\Field\FieldItemListInterface::class);
    $typeField->value = 'apartment';

    $natureField = $this->createStub(\Drupal\Core\Field\FieldItemListInterface::class);
    $natureField->value = 'residential';

    $division->method('get')->willReturnMap([
      ['type', $typeField],
      ['nature', $natureField],
    ]);

    $summary = $this->divisionManager->getSummary($division);

    $this->assertSame(42, $summary['id']);
    $this->assertSame('Test Building', $summary['building_name']);
    $this->assertSame('apartment', $summary['type']);
    $this->assertSame('residential', $summary['nature']);
    $this->assertSame('LOT-123', $summary['lot']);
    $this->assertSame(75.50, $summary['total_surface']);
  }

}
