<?php

declare(strict_types=1);

namespace Drupal\Tests\ps_division\Unit\Plugin\Field\FieldType;

use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\TypedData\DataDefinitionInterface;
use Drupal\ps_division\Plugin\Field\FieldType\SurfaceItem;
use Drupal\Tests\UnitTestCase;

/**
 * Unit tests for SurfaceItem field type.
 *
 * @coversDefaultClass \Drupal\ps_division\Plugin\Field\FieldType\SurfaceItem
 * @group ps_division
 */
final class SurfaceItemTest extends UnitTestCase {

  /**
   * @covers ::propertyDefinitions
   */
  public function testPropertyDefinitionsReturnsExpectedStructure(): void {
    $fieldDefinition = $this->createMock(FieldDefinitionInterface::class);
    $definitions = SurfaceItem::propertyDefinitions($fieldDefinition);

    $this->assertArrayHasKey('value', $definitions);
    $this->assertArrayHasKey('unit', $definitions);
    $this->assertArrayHasKey('type', $definitions);
    $this->assertArrayHasKey('nature', $definitions);
    $this->assertArrayHasKey('qualification', $definitions);

    $this->assertInstanceOf(DataDefinitionInterface::class, $definitions['value']);
    $this->assertInstanceOf(DataDefinitionInterface::class, $definitions['unit']);
  }

  /**
   * @covers ::schema
   */
  public function testSchemaReturnsExpectedColumns(): void {
    $fieldDefinition = $this->createMock(FieldDefinitionInterface::class);
    $item = new SurfaceItem([$fieldDefinition]);
    $schema = $item::schema($fieldDefinition);

    $this->assertArrayHasKey('columns', $schema);
    $this->assertArrayHasKey('value', $schema['columns']);
    $this->assertArrayHasKey('unit', $schema['columns']);
    $this->assertArrayHasKey('type', $schema['columns']);
    $this->assertArrayHasKey('nature', $schema['columns']);
    $this->assertArrayHasKey('qualification', $schema['columns']);

    // Value should be decimal.
    $this->assertSame('numeric', $schema['columns']['value']['type']);
    $this->assertSame(10, $schema['columns']['value']['precision']);
    $this->assertSame(2, $schema['columns']['value']['scale']);

    // Others should be varchar.
    $this->assertSame('varchar', $schema['columns']['unit']['type']);
    $this->assertSame('varchar', $schema['columns']['type']['type']);
  }

  /**
   * @covers ::isEmpty
   */
  public function testIsEmptyReturnsTrueWhenValueIsNull(): void {
    $fieldDefinition = $this->createMock(FieldDefinitionInterface::class);
    $item = new SurfaceItem([$fieldDefinition]);
    $item->setValue(['value' => NULL]);

    $this->assertTrue($item->isEmpty());
  }

  /**
   * @covers ::isEmpty
   */
  public function testIsEmptyReturnsTrueWhenValueIsEmptyString(): void {
    $fieldDefinition = $this->createMock(FieldDefinitionInterface::class);
    $item = new SurfaceItem([$fieldDefinition]);
    $item->setValue(['value' => '']);

    $this->assertTrue($item->isEmpty());
  }

  /**
   * @covers ::isEmpty
   */
  public function testIsEmptyReturnsFalseWhenValueIsZero(): void {
    $fieldDefinition = $this->createMock(FieldDefinitionInterface::class);
    $item = new SurfaceItem([$fieldDefinition]);
    $item->setValue(['value' => 0]);

    $this->assertFalse($item->isEmpty());
  }

  /**
   * @covers ::isEmpty
   */
  public function testIsEmptyReturnsFalseWhenValueIsPositive(): void {
    $fieldDefinition = $this->createMock(FieldDefinitionInterface::class);
    $item = new SurfaceItem([$fieldDefinition]);
    $item->setValue(['value' => 50.5]);

    $this->assertFalse($item->isEmpty());
  }

  /**
   * @covers ::getValue
   */
  public function testGetValueReturnsNumericValue(): void {
    $fieldDefinition = $this->createMock(FieldDefinitionInterface::class);
    $item = new SurfaceItem([$fieldDefinition]);
    $item->setValue(['value' => 75.25]);

    $this->assertSame(75.25, $item->getValue());
  }

  /**
   * @covers ::getUnit
   */
  public function testGetUnitReturnsNullWhenNotSet(): void {
    $fieldDefinition = $this->createMock(FieldDefinitionInterface::class);
    $item = new SurfaceItem([$fieldDefinition]);
    $item->setValue(['value' => 50]);

    $this->assertNull($item->getUnit());
  }

  /**
   * @covers ::getUnit
   */
  public function testGetUnitReturnsSetValue(): void {
    $fieldDefinition = $this->createMock(FieldDefinitionInterface::class);
    $item = new SurfaceItem([$fieldDefinition]);
    $item->setValue(['value' => 50, 'unit' => 'm2']);

    $this->assertSame('m2', $item->getUnit());
  }

  /**
   * @covers ::getType
   */
  public function testGetTypeReturnsSetValue(): void {
    $fieldDefinition = $this->createMock(FieldDefinitionInterface::class);
    $item = new SurfaceItem([$fieldDefinition]);
    $item->setValue(['value' => 50, 'type' => 'living']);

    $this->assertSame('living', $item->getType());
  }

  /**
   * @covers ::getNature
   */
  public function testGetNatureReturnsSetValue(): void {
    $fieldDefinition = $this->createMock(FieldDefinitionInterface::class);
    $item = new SurfaceItem([$fieldDefinition]);
    $item->setValue(['value' => 50, 'nature' => 'habitable']);

    $this->assertSame('habitable', $item->getNature());
  }

  /**
   * @covers ::getQualification
   */
  public function testGetQualificationReturnsSetValue(): void {
    $fieldDefinition = $this->createMock(FieldDefinitionInterface::class);
    $item = new SurfaceItem([$fieldDefinition]);
    $item->setValue(['value' => 50, 'qualification' => 'carrez']);

    $this->assertSame('carrez', $item->getQualification());
  }

}
