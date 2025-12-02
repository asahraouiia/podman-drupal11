<?php

declare(strict_types=1);

namespace Drupal\Tests\ps_features\Unit\Service;

use Drupal\ps_features\Entity\FeatureInterface;
use Drupal\ps_features\Service\CompareBuilder;
use Drupal\ps_features\Service\FeatureManagerInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Config\Config;
use PHPUnit\Framework\TestCase;

/**
 * @group ps_features
 * @coversDefaultClass \Drupal\ps_features\Service\CompareBuilder
 */
final class CompareBuilderTest extends TestCase {

  /**
   *
   */
  private function createFeature(string $id, string $label, ?string $group, int $weight): FeatureInterface {
    $mock = $this->createMock(FeatureInterface::class);
    $mock->method('id')->willReturn($id);
    $mock->method('label')->willReturn($label);
    $mock->method('getValueType')->willReturn('flag');
    $mock->method('getDictionaryType')->willReturn(NULL);
    $mock->method('getUnit')->willReturn(NULL);
    $mock->method('isRequired')->willReturn(FALSE);
    $mock->method('getValidationRules')->willReturn([]);
    $mock->method('getGroup')->willReturn($group);
    $mock->method('getWeight')->willReturn($weight);
    $mock->method('getMetadata')->willReturn([]);
    $mock->method('getDescription')->willReturn(NULL);
    $mock->method('isFacetable')->willReturn(FALSE);
    return $mock;
  }

  /**
   * @covers ::build
   * @covers ::getSections
   */
  public function testBuildComparisonSections(): void {
    $features = [
      $this->createFeature('f1', 'Feature 1', 'general', 5),
      $this->createFeature('f2', 'Feature 2', 'comfort', 0),
      $this->createFeature('f3', 'Feature 3', 'comfort', 10),
      $this->createFeature('f4', 'Feature 4', NULL, 0),
      $this->createFeature('f5', 'Feature 5', 'technical', 3),
    ];

    $config = $this->createMock(Config::class);
    $config->method('get')->with('compare_sections')->willReturn(['general', 'comfort', 'technical']);
    $configFactory = $this->createMock(ConfigFactoryInterface::class);
    $configFactory->method('get')->with('ps_features.settings')->willReturn($config);

    $featureManager = $this->createMock(FeatureManagerInterface::class);
    $featureManager->method('getFeatures')->willReturn([]);

    $builder = new CompareBuilder($configFactory, $featureManager);
    $sections = $builder->build($features);

    // Expect ordered keys.
    $this->assertSame(['general', 'comfort', 'technical', 'other'], array_keys($sections));
    // Comfort features sorted by weight: f2 then f3.
    $comfortIds = array_map(static fn($f) => $f->id(), $sections['comfort']['features']);
    $this->assertSame(['f2', 'f3'], $comfortIds);
    // Other bucket contains f4.
    $otherIds = array_map(static fn($f) => $f->id(), $sections['other']['features']);
    $this->assertSame(['f4'], $otherIds);
  }

}
