<?php

declare(strict_types=1);

namespace Drupal\Tests\ps_features\Unit\Service;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\ps_features\Entity\FeatureInterface;
use Drupal\ps_features\Service\FeatureManager;
use Drupal\Tests\UnitTestCase;
use Psr\Log\LoggerInterface;

/**
 * @coversDefaultClass \Drupal\ps_features\Service\FeatureManager
 * @group ps_features
 */
final class FeatureManagerTest extends UnitTestCase {

  /**
   * The entity type manager mock.
   */
  private EntityTypeManagerInterface $entityTypeManager;

  /**
   * The cache backend mock.
   */
  private CacheBackendInterface $cache;

  /**
   * The logger mock.
   */
  private LoggerInterface $logger;

  /**
   * The feature manager instance.
   */
  private FeatureManager $manager;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->entityTypeManager = $this->createMock(EntityTypeManagerInterface::class);
    $this->cache = $this->createMock(CacheBackendInterface::class);
    $this->logger = $this->createMock(LoggerInterface::class);

    // Mock string translation.
    $stringTranslation = $this->getStringTranslationStub();

    $this->manager = new FeatureManager(
      $this->entityTypeManager,
      $this->cache,
      $this->logger,
      NULL,
      $stringTranslation
    );
  }

  /**
   * @covers ::getFeature
   */
  public function testGetFeature(): void {
    $featureMock = $this->createMock(FeatureInterface::class);
    $featureMock->method('id')->willReturn('has_parking');

    $storage = $this->createMock(EntityStorageInterface::class);
    $storage->method('loadMultiple')->willReturn(['has_parking' => $featureMock]);

    $this->entityTypeManager
      ->method('getStorage')
      ->with('ps_feature')
      ->willReturn($storage);

    $this->cache
      ->method('get')
      ->willReturn(FALSE);

    $result = $this->manager->getFeature('has_parking');

    $this->assertSame($featureMock, $result);
  }

  /**
   * @covers ::getFeature
   */
  public function testGetFeatureNotFound(): void {
    $storage = $this->createMock(EntityStorageInterface::class);
    $storage->method('loadMultiple')->willReturn([]);

    $this->entityTypeManager
      ->method('getStorage')
      ->with('ps_feature')
      ->willReturn($storage);

    $this->cache
      ->method('get')
      ->willReturn(FALSE);

    $result = $this->manager->getFeature('nonexistent');

    $this->assertNull($result);
  }

  /**
   * @covers ::getFeatures
   */
  public function testGetFeaturesNoFilter(): void {
    $feature1 = $this->createMock(FeatureInterface::class);
    $feature2 = $this->createMock(FeatureInterface::class);

    $storage = $this->createMock(EntityStorageInterface::class);
    $storage->method('loadMultiple')->willReturn([
      'feature1' => $feature1,
      'feature2' => $feature2,
    ]);

    $this->entityTypeManager
      ->method('getStorage')
      ->with('ps_feature')
      ->willReturn($storage);

    $this->cache
      ->method('get')
      ->willReturn(FALSE);

    $result = $this->manager->getFeatures();

    $this->assertCount(2, $result);
    $this->assertArrayHasKey('feature1', $result);
    $this->assertArrayHasKey('feature2', $result);
  }

  /**
   * @covers ::getFeatures
   */
  public function testGetFeaturesWithValueTypeFilter(): void {
    $feature1 = $this->createMock(FeatureInterface::class);
    $feature1->method('getValueType')->willReturn('flag');

    $feature2 = $this->createMock(FeatureInterface::class);
    $feature2->method('getValueType')->willReturn('numeric');

    $storage = $this->createMock(EntityStorageInterface::class);
    $storage->method('loadMultiple')->willReturn([
      'feature1' => $feature1,
      'feature2' => $feature2,
    ]);

    $this->entityTypeManager
      ->method('getStorage')
      ->with('ps_feature')
      ->willReturn($storage);

    $this->cache
      ->method('get')
      ->willReturn(FALSE);

    $result = $this->manager->getFeatures(['value_type' => 'flag']);

    $this->assertCount(1, $result);
    $this->assertArrayHasKey('feature1', $result);
  }

  /**
   * @covers ::featureExists
   */
  public function testFeatureExists(): void {
    $featureMock = $this->createMock(FeatureInterface::class);

    $storage = $this->createMock(EntityStorageInterface::class);
    $storage->method('loadMultiple')->willReturn(['has_parking' => $featureMock]);

    $this->entityTypeManager
      ->method('getStorage')
      ->with('ps_feature')
      ->willReturn($storage);

    $this->cache
      ->method('get')
      ->willReturn(FALSE);

    $this->assertTrue($this->manager->featureExists('has_parking'));
    $this->assertFalse($this->manager->featureExists('nonexistent'));
  }

  /**
   * @covers ::getValueTypes
   */
  public function testGetValueTypes(): void {
    $result = $this->manager->getValueTypes();

    $this->assertIsArray($result);
    $this->assertArrayHasKey('flag', $result);
    $this->assertArrayHasKey('yesno', $result);
    $this->assertArrayHasKey('numeric', $result);
    $this->assertArrayHasKey('range', $result);
    $this->assertArrayHasKey('dictionary', $result);
    $this->assertArrayHasKey('string', $result);
  }

  /**
   * @covers ::clearCache
   */
  public function testClearCache(): void {
    $this->cache
      ->expects($this->once())
      ->method('delete')
      ->with('ps_features:all');

    $this->logger
      ->expects($this->once())
      ->method('info')
      ->with('Feature cache cleared.');

    $this->manager->clearCache();
  }

}
