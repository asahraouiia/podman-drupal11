<?php

declare(strict_types=1);

namespace Drupal\Tests\ps_diagnostic\Unit\Plugin\Field\FieldType;

use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\TypedData\DataDefinitionInterface;
use Drupal\ps_diagnostic\Plugin\Field\FieldType\DiagnosticItem;
use Drupal\Tests\UnitTestCase;

/**
 * Tests for DiagnosticItem field type.
 *
 * @group ps_diagnostic
 * @coversDefaultClass \Drupal\ps_diagnostic\Plugin\Field\FieldType\DiagnosticItem
 */
class DiagnosticItemTest extends UnitTestCase {

  /**
   * Tests isEmpty() with all fields empty.
   *
   * @covers ::isEmpty
   */
  public function testIsEmptyWithAllFieldsEmpty(): void {
    $item = $this->createDiagnosticItem([]);
    $this->assertTrue($item->isEmpty());
  }

  /**
   * Tests isEmpty() with only type_id set.
   *
   * @covers ::isEmpty
   */
  public function testIsEmptyWithOnlyTypeId(): void {
    $item = $this->createDiagnosticItem(['type_id' => 'dpe']);
    $this->assertTrue($item->isEmpty(), 'Item with only type_id should be empty');
  }

  /**
   * Tests isEmpty() with type_id and numeric value.
   *
   * @covers ::isEmpty
   */
  public function testIsNotEmptyWithTypeIdAndValue(): void {
    $item = $this->createDiagnosticItem([
      'type_id' => 'dpe',
      'value_numeric' => 120.5,
    ]);
    $this->assertFalse($item->isEmpty());
  }

  /**
   * Tests isEmpty() with type_id and label_code.
   *
   * @covers ::isEmpty
   */
  public function testIsNotEmptyWithTypeIdAndClass(): void {
    $item = $this->createDiagnosticItem([
      'type_id' => 'ges',
      'label_code' => 'C',
    ]);
    $this->assertFalse($item->isEmpty());
  }

  /**
   * Tests isEmpty() with date only.
   *
   * @covers ::isEmpty
   */
  public function testIsNotEmptyWithDate(): void {
    $item = $this->createDiagnosticItem([
      'type_id' => 'dpe',
      'valid_from' => '2025-01-15',
    ]);
    $this->assertFalse($item->isEmpty());
  }

  /**
   * Tests isEmpty() with flag set.
   *
   * @covers ::isEmpty
   */
  public function testIsNotEmptyWithFlag(): void {
    $item = $this->createDiagnosticItem([
      'type_id' => 'dpe',
      'no_classification' => TRUE,
    ]);
    $this->assertFalse($item->isEmpty());
  }

  /**
   * Tests setValue() normalizes empty string to NULL for value_numeric.
   *
   * @covers ::setValue
   */
  public function testSetValueNormalizesEmptyNumeric(): void {
    $item = $this->createDiagnosticItem([]);
    $item->setValue(['value_numeric' => ''], FALSE);
    $this->assertNull($item->value_numeric);
  }

  /**
   * Tests setValue() casts string numeric to float.
   *
   * @covers ::setValue
   */
  public function testSetValueCastsNumericString(): void {
    $item = $this->createDiagnosticItem([]);
    $item->setValue(['value_numeric' => '123.45'], FALSE);
    $this->assertSame(123.45, $item->value_numeric);
  }

  /**
   * Tests setValue() normalizes empty label_code to NULL.
   *
   * @covers ::setValue
   */
  public function testSetValueNormalizesEmptyLabelCode(): void {
    $item = $this->createDiagnosticItem([]);
    $item->setValue(['label_code' => ''], FALSE);
    $this->assertNull($item->label_code);
  }

  /**
   * Tests setValue() normalizes empty dates to NULL.
   *
   * @covers ::setValue
   */
  public function testSetValueNormalizesEmptyDates(): void {
    $item = $this->createDiagnosticItem([]);
    $item->setValue([
      'valid_from' => '',
      'valid_to' => '',
    ], FALSE);
    $this->assertNull($item->valid_from);
    $this->assertNull($item->valid_to);
  }

  /**
   * Tests setValue() casts boolean flags.
   *
   * @covers ::setValue
   */
  public function testSetValueCastsBooleanFlags(): void {
    $item = $this->createDiagnosticItem([]);
    $item->setValue([
      'no_classification' => 1,
      'non_applicable' => 0,
    ], FALSE);
    $this->assertTrue($item->no_classification);
    $this->assertFalse($item->non_applicable);
  }

  /**
   * Creates a diagnostic item with given values.
   *
   * @param array $values
   *   Field values.
   *
   * @return \Drupal\ps_diagnostic\Plugin\Field\FieldType\DiagnosticItem
   *   The item.
   */
  private function createDiagnosticItem(array $values): DiagnosticItem {
    $definition = $this->createMock(DataDefinitionInterface::class);
    $fieldDefinition = $this->createMock(FieldDefinitionInterface::class);

    $item = new DiagnosticItem($definition);
    $item->setValue($values, FALSE);

    return $item;
  }

}
