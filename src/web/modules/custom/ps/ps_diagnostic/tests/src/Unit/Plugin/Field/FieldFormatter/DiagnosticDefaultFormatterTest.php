<?php

declare(strict_types=1);

namespace Drupal\Tests\ps_diagnostic\Unit\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\ps_diagnostic\Plugin\Field\FieldFormatter\DiagnosticDefaultFormatter;
use Drupal\ps_diagnostic\Plugin\Field\FieldType\DiagnosticItem;
use Drupal\ps_diagnostic\Service\DiagnosticClassCalculatorInterface;
use Drupal\Tests\UnitTestCase;

/**
 * Tests for DiagnosticDefaultFormatter.
 *
 * @group ps_diagnostic
 * @coversDefaultClass \Drupal\ps_diagnostic\Plugin\Field\FieldFormatter\DiagnosticDefaultFormatter
 */
class DiagnosticDefaultFormatterTest extends UnitTestCase {

  /**
   * The mocked class calculator.
   */
  private DiagnosticClassCalculatorInterface $calculator;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->calculator = $this->createMock(DiagnosticClassCalculatorInterface::class);
    $this->calculator->method('getDisplayInfo')
      ->willReturn([
        'class' => 'C',
        'color' => '#F7941D',
        'unit' => 'kWh/m²/an',
        'display_text' => 'C',
        'is_special' => FALSE,
      ]);
  }

  /**
   * Tests default settings.
   *
   * @covers ::defaultSettings
   */
  public function testDefaultSettings(): void {
    $settings = DiagnosticDefaultFormatter::defaultSettings();

    $this->assertTrue($settings['show_value']);
    $this->assertFalse($settings['show_dates']);
    $this->assertSame('horizontal', $settings['layout']);
    $this->assertTrue($settings['dim_empty']);
    $this->assertSame(30, $settings['dim_opacity']);
  }

  /**
   * Tests dimmed state is TRUE when no value and no class.
   *
   * @covers ::viewElements
   */
  public function testDimmedStateWithEmptyValueAndClass(): void {
    $item = $this->createMockItem([
      'type_id' => 'dpe',
      'value_numeric' => NULL,
      'label_code' => '',
    ]);

    $items = $this->createMockItemList([$item]);
    $formatter = $this->createFormatter(['dim_empty' => TRUE, 'dim_opacity' => 30]);

    $elements = $formatter->viewElements($items, 'en');

    $this->assertArrayHasKey(0, $elements);
    $this->assertTrue($elements[0]['#is_dimmed']);
    $this->assertSame(30, $elements[0]['#dim_opacity']);
  }

  /**
   * Tests dimmed state is FALSE when value exists.
   *
   * @covers ::viewElements
   */
  public function testDimmedStateWithValue(): void {
    $item = $this->createMockItem([
      'type_id' => 'dpe',
      'value_numeric' => 120.0,
      'label_code' => '',
    ]);

    $items = $this->createMockItemList([$item]);
    $formatter = $this->createFormatter(['dim_empty' => TRUE]);

    $elements = $formatter->viewElements($items, 'en');

    $this->assertFalse($elements[0]['#is_dimmed']);
  }

  /**
   * Tests dimmed state is FALSE when class exists.
   *
   * @covers ::viewElements
   */
  public function testDimmedStateWithClass(): void {
    $item = $this->createMockItem([
      'type_id' => 'ges',
      'value_numeric' => NULL,
      'label_code' => 'B',
    ]);

    $items = $this->createMockItemList([$item]);
    $formatter = $this->createFormatter(['dim_empty' => TRUE]);

    $elements = $formatter->viewElements($items, 'en');

    $this->assertFalse($elements[0]['#is_dimmed']);
  }

  /**
   * Tests dimmed state is FALSE when dim_empty setting is disabled.
   *
   * @covers ::viewElements
   */
  public function testDimmedStateDisabled(): void {
    $item = $this->createMockItem([
      'type_id' => 'dpe',
      'value_numeric' => NULL,
      'label_code' => '',
    ]);

    $items = $this->createMockItemList([$item]);
    $formatter = $this->createFormatter(['dim_empty' => FALSE]);

    $elements = $formatter->viewElements($items, 'en');

    $this->assertFalse($elements[0]['#is_dimmed']);
  }

  /**
   * Tests custom opacity setting.
   *
   * @covers ::viewElements
   */
  public function testCustomOpacitySetting(): void {
    $item = $this->createMockItem([
      'type_id' => 'dpe',
      'value_numeric' => NULL,
      'label_code' => '',
    ]);

    $items = $this->createMockItemList([$item]);
    $formatter = $this->createFormatter(['dim_empty' => TRUE, 'dim_opacity' => 50]);

    $elements = $formatter->viewElements($items, 'en');

    $this->assertSame(50, $elements[0]['#dim_opacity']);
  }

  /**
   * Creates a mock formatter with given settings.
   *
   * @param array $settings
   *   Formatter settings.
   *
   * @return \Drupal\ps_diagnostic\Plugin\Field\FieldFormatter\DiagnosticDefaultFormatter
   *   The formatter.
   */
  private function createFormatter(array $settings): DiagnosticDefaultFormatter {
    $fieldDefinition = $this->createMock(FieldDefinitionInterface::class);
    $fieldDefinition->method('getName')->willReturn('field_diagnostic');

    $defaultSettings = DiagnosticDefaultFormatter::defaultSettings();
    $mergedSettings = array_merge($defaultSettings, $settings);

    return new DiagnosticDefaultFormatter(
      'ps_diagnostic_default',
      [],
      $fieldDefinition,
      $mergedSettings,
      'full',
      'full',
      [],
      $this->calculator
    );
  }

  /**
   * Creates a mock diagnostic item.
   *
   * @param array $values
   *   Field values.
   *
   * @return \Drupal\ps_diagnostic\Plugin\Field\FieldType\DiagnosticItem
   *   The mock item.
   */
  private function createMockItem(array $values): DiagnosticItem {
    $item = $this->createMock(DiagnosticItem::class);
    foreach ($values as $key => $value) {
      $item->$key = $value;
    }
    return $item;
  }

  /**
   * Creates a mock item list.
   *
   * @param array $items
   *   Array of items.
   *
   * @return \Drupal\Core\Field\FieldItemListInterface
   *   The mock list.
   */
  private function createMockItemList(array $items): FieldItemListInterface {
    $itemList = $this->createMock(FieldItemListInterface::class);
    $itemList->method('getIterator')->willReturn(new \ArrayIterator($items));
    return $itemList;
  }

}
