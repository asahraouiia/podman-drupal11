<?php

declare(strict_types=1);

namespace Drupal\Tests\ps_features\Unit\Service;

use Drupal\ps_features\Entity\FeatureInterface;
use Drupal\ps_features\Service\SearchFacetsBuilder;
use Drupal\ps_features\Service\FeatureManagerInterface;
use PHPUnit\Framework\TestCase;

/**
 * @group ps_features
 * @coversDefaultClass \Drupal\ps_features\Service\SearchFacetsBuilder
 */
final class SearchFacetsBuilderTest extends TestCase {

  /**
   * Creates a mock FeatureInterface.
   */
  private function createFeature(
    string $id,
    string $label,
    string $valueType,
    bool $facetable = TRUE,
    ?string $dictionaryType = NULL,
    ?string $unit = NULL,
  ): FeatureInterface {
    $mock = $this->createMock(FeatureInterface::class);
    $mock->method('id')->willReturn($id);
    $mock->method('label')->willReturn($label);
    $mock->method('getValueType')->willReturn($valueType);
    $mock->method('getDictionaryType')->willReturn($dictionaryType);
    $mock->method('getUnit')->willReturn($unit);
    $mock->method('isRequired')->willReturn(FALSE);
    $mock->method('getValidationRules')->willReturn([]);
    $mock->method('getGroup')->willReturn(NULL);
    $mock->method('getWeight')->willReturn(0);
    $mock->method('getMetadata')->willReturn([]);
    $mock->method('getDescription')->willReturn(NULL);
    $mock->method('isFacetable')->willReturn($facetable);
    return $mock;
  }

  /**
   * @covers ::build
   * @covers ::getFacetType
   * @covers ::getWidgetType
   */
  public function testBuildFacets(): void {
    $features = [
      $this->createFeature('has_elevator', 'Has elevator', 'flag'),
      $this->createFeature('air_conditioning', 'Air conditioning', 'dictionary', TRUE, 'air_conditioning_type'),
      $this->createFeature('floor_number', 'Floor number', 'numeric'),
      $this->createFeature('storage_height', 'Storage height', 'range', TRUE, NULL, 'm'),
      $this->createFeature('internal_code', 'Internal code', 'string', FALSE),
    ];

    $featureManager = $this->createMock(FeatureManagerInterface::class);
    $featureManager->method('getFeatures')->willReturn($features);

    $builder = new SearchFacetsBuilder($featureManager);
    $definitions = $builder->build();

    // Should skip non-facetable string feature.
    $this->assertCount(4, $definitions);
    $ids = array_column($definitions, 'id');
    $this->assertNotContains('internal_code', $ids);

    // Check mapping.
    $map = [];
    foreach ($definitions as $def) {
      $map[$def['id']] = $def;
    }
    $this->assertSame('boolean', $map['has_elevator']['facet_type']);
    $this->assertSame('terms', $map['air_conditioning']['facet_type']);
    $this->assertSame('numeric', $map['floor_number']['facet_type']);
    $this->assertSame('range', $map['storage_height']['facet_type']);
    $this->assertSame('m', $map['storage_height']['unit']);
  }

}
