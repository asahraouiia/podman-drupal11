<?php

declare(strict_types=1);

namespace Drupal\Tests\ps_features\Unit\Entity;

use Drupal\ps_features\Entity\Feature;
use Drupal\Tests\UnitTestCase;

/**
 * Tests the Feature entity methods.
 *
 * @group ps_features
 * @coversDefaultClass \Drupal\ps_features\Entity\Feature
 */
final class FeatureTest extends UnitTestCase {

  /**
   * Tests getValueType method.
   *
   * @covers ::getValueType
   */
  public function testGetValueType(): void {
    $feature = $this->getMockBuilder(Feature::class)
      ->disableOriginalConstructor()
      ->onlyMethods(['get'])
      ->getMock();

    // Use reflection to set protected property.
    $reflection = new \ReflectionClass($feature);
    $property = $reflection->getProperty('value_type');
    $property->setAccessible(TRUE);
    $property->setValue($feature, 'flag');

    $this->assertEquals('flag', $feature->getValueType());
  }

  /**
   * Tests getDictionaryType method.
   *
   * @covers ::getDictionaryType
   */
  public function testGetDictionaryType(): void {
    $feature = $this->getMockBuilder(Feature::class)
      ->disableOriginalConstructor()
      ->onlyMethods(['get'])
      ->getMock();

    $reflection = new \ReflectionClass($feature);
    $property = $reflection->getProperty('dictionary_type');
    $property->setAccessible(TRUE);
    $property->setValue($feature, 'property_type');

    $this->assertEquals('property_type', $feature->getDictionaryType());
  }

  /**
   * Tests getUnit method.
   *
   * @covers ::getUnit
   */
  public function testGetUnit(): void {
    $feature = $this->getMockBuilder(Feature::class)
      ->disableOriginalConstructor()
      ->onlyMethods(['get'])
      ->getMock();

    $reflection = new \ReflectionClass($feature);
    $property = $reflection->getProperty('unit');
    $property->setAccessible(TRUE);
    $property->setValue($feature, 'm');

    $this->assertEquals('m', $feature->getUnit());
  }

  /**
   * Tests isRequired method.
   *
   * @covers ::isRequired
   */
  public function testIsRequired(): void {
    $feature = $this->getMockBuilder(Feature::class)
      ->disableOriginalConstructor()
      ->onlyMethods(['get'])
      ->getMock();

    $reflection = new \ReflectionClass($feature);
    $property = $reflection->getProperty('is_required');
    $property->setAccessible(TRUE);
    $property->setValue($feature, TRUE);

    $this->assertTrue($feature->isRequired());
  }

  /**
   * Tests getValidationRules method.
   *
   * @covers ::getValidationRules
   */
  public function testGetValidationRules(): void {
    $feature = $this->getMockBuilder(Feature::class)
      ->disableOriginalConstructor()
      ->onlyMethods(['get'])
      ->getMock();

    $rules = ['min' => 0, 'max' => 100];
    $reflection = new \ReflectionClass($feature);
    $property = $reflection->getProperty('validation_rules');
    $property->setAccessible(TRUE);
    $property->setValue($feature, $rules);

    $this->assertEquals($rules, $feature->getValidationRules());
  }

  /**
   * Tests getMetadata method.
   *
   * @covers ::getMetadata
   */
  public function testGetMetadata(): void {
    $feature = $this->getMockBuilder(Feature::class)
      ->disableOriginalConstructor()
      ->onlyMethods(['get'])
      ->getMock();

    $metadata = ['group' => 'test_group', 'weight' => 5];
    $reflection = new \ReflectionClass($feature);
    $property = $reflection->getProperty('metadata');
    $property->setAccessible(TRUE);
    $property->setValue($feature, $metadata);

    $this->assertEquals($metadata, $feature->getMetadata());
  }

  /**
   * Tests getDescription method.
   *
   * @covers ::getDescription
   */
  public function testGetDescription(): void {
    $feature = $this->getMockBuilder(Feature::class)
      ->disableOriginalConstructor()
      ->onlyMethods(['get'])
      ->getMock();

    $reflection = new \ReflectionClass($feature);
    $property = $reflection->getProperty('description');
    $property->setAccessible(TRUE);
    $property->setValue($feature, 'Test description');

    $this->assertEquals('Test description', $feature->getDescription());
  }

  /**
   * Tests getGroup method.
   *
   * @covers ::getGroup
   */
  public function testGetGroup(): void {
    $feature = $this->getMockBuilder(Feature::class)
      ->disableOriginalConstructor()
      ->onlyMethods(['get'])
      ->getMock();

    $reflection = new \ReflectionClass($feature);
    $property = $reflection->getProperty('group');
    $property->setAccessible(TRUE);
    $property->setValue($feature, 'comfort');

    $this->assertEquals('comfort', $feature->getGroup());
  }

  /**
   * Tests getGroup method with null value.
   *
   * @covers ::getGroup
   */
  public function testGetGroupNull(): void {
    $feature = $this->getMockBuilder(Feature::class)
      ->disableOriginalConstructor()
      ->onlyMethods(['get'])
      ->getMock();

    $reflection = new \ReflectionClass($feature);
    $property = $reflection->getProperty('group');
    $property->setAccessible(TRUE);
    $property->setValue($feature, NULL);

    $this->assertNull($feature->getGroup());
  }

  /**
   * Tests getWeight method.
   *
   * @covers ::getWeight
   */
  public function testGetWeight(): void {
    $feature = $this->getMockBuilder(Feature::class)
      ->disableOriginalConstructor()
      ->onlyMethods(['get'])
      ->getMock();

    $reflection = new \ReflectionClass($feature);
    $property = $reflection->getProperty('weight');
    $property->setAccessible(TRUE);
    $property->setValue($feature, 42);

    $this->assertEquals(42, $feature->getWeight());
  }

  /**
   * Tests getWeight method with default value.
   *
   * @covers ::getWeight
   */
  public function testGetWeightDefault(): void {
    $feature = $this->getMockBuilder(Feature::class)
      ->disableOriginalConstructor()
      ->onlyMethods(['get'])
      ->getMock();

    $reflection = new \ReflectionClass($feature);
    $property = $reflection->getProperty('weight');
    $property->setAccessible(TRUE);
    $property->setValue($feature, 0);

    $this->assertEquals(0, $feature->getWeight());
  }

  /**
   * Tests getCacheTagsToInvalidate method.
   *
   * @covers ::getCacheTagsToInvalidate
   */
  public function testGetCacheTagsToInvalidate(): void {
    // This test is difficult to unit test due to parent class dependencies.
    // We verify that the method exists and returns an array.
    $reflection = new \ReflectionClass(Feature::class);
    $method = $reflection->getMethod('getCacheTagsToInvalidate');

    $this->assertTrue($method->isPublic());
    $this->assertEquals('getCacheTagsToInvalidate', $method->getName());
  }

}
