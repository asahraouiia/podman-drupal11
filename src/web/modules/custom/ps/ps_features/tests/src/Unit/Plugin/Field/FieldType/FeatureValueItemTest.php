<?php

declare(strict_types=1);

namespace Drupal\Tests\ps_features\Unit\Plugin\Field\FieldType;

use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\ps_features\Plugin\Field\FieldType\FeatureValueItem;
use Drupal\Tests\UnitTestCase;

/**
 * Tests the FeatureValueItem field type.
 *
 * @group ps_features
 * @coversDefaultClass \Drupal\ps_features\Plugin\Field\FieldType\FeatureValueItem
 */
final class FeatureValueItemTest extends UnitTestCase {

  /**
   * Tests property definitions.
   *
   * @covers ::propertyDefinitions
   */
  public function testPropertyDefinitions(): void {
    $fieldStorageDefinition = $this->createMock(FieldStorageDefinitionInterface::class);
    $properties = FeatureValueItem::propertyDefinitions($fieldStorageDefinition);

    $this->assertArrayHasKey('feature_id', $properties);
    $this->assertArrayHasKey('value_type', $properties);
    $this->assertArrayHasKey('dictionary_type', $properties);
    $this->assertArrayHasKey('value_boolean', $properties);
    $this->assertArrayHasKey('value_string', $properties);
    $this->assertArrayHasKey('value_numeric', $properties);
    $this->assertArrayHasKey('value_range_min', $properties);
    $this->assertArrayHasKey('value_range_max', $properties);
    $this->assertArrayHasKey('unit', $properties);
    $this->assertArrayHasKey('complement', $properties);
    $this->assertCount(10, $properties);
  }

  /**
   * Tests field schema.
   *
   * @covers ::schema
   */
  public function testSchema(): void {
    $fieldStorageDefinition = $this->createMock(FieldStorageDefinitionInterface::class);
    $schema = FeatureValueItem::schema($fieldStorageDefinition);

    $this->assertArrayHasKey('columns', $schema);
    $this->assertArrayHasKey('indexes', $schema);

    // Check columns.
    $this->assertArrayHasKey('feature_id', $schema['columns']);
    $this->assertArrayHasKey('value_type', $schema['columns']);
    $this->assertArrayHasKey('value_boolean', $schema['columns']);
    $this->assertArrayHasKey('value_numeric', $schema['columns']);
    $this->assertArrayHasKey('value_range_min', $schema['columns']);
    $this->assertArrayHasKey('value_range_max', $schema['columns']);

    // Check indexes.
    $this->assertArrayHasKey('feature_id', $schema['indexes']);
    $this->assertArrayHasKey('value_type', $schema['indexes']);
  }

}
