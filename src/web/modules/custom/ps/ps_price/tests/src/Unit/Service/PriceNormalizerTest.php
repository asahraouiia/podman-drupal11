<?php

declare(strict_types=1);

namespace Drupal\Tests\ps_price\Unit\Service;

use Drupal\Core\Field\FieldItemInterface;
use Drupal\ps_price\Service\PriceNormalizer;
use PHPUnit\Framework\TestCase;

/**
 * Tests for PriceNormalizer service.
 *
 * @coversDefaultClass \Drupal\ps_price\Service\PriceNormalizer
 * @group ps_price
 */
class PriceNormalizerTest extends TestCase {

  /**
   * The price normalizer service.
   *
   * @var \Drupal\ps_price\Service\PriceNormalizer
   */
  private PriceNormalizer $normalizer;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->normalizer = new PriceNormalizer();
  }

  /**
   * Tests normalization with monthly period.
   *
   * @covers ::normalize
   */
  public function testNormalizeMonthlyToYearly(): void {
    $item = $this->createMockItem([
      'amount' => 100,
      'period' => 'month',
      'unit' => '/m²',
    ]);

    $result = $this->normalizer->normalize($item);
    $this->assertEquals(1200, $result);
  }

  /**
   * Tests normalization with quarterly period.
   *
   * @covers ::normalize
   */
  public function testNormalizeQuarterlyToYearly(): void {
    $item = $this->createMockItem([
      'amount' => 300,
      'period' => 'quarter',
      'unit' => '/m²',
    ]);

    $result = $this->normalizer->normalize($item);
    $this->assertEquals(1200, $result);
  }

  /**
   * Tests normalization with weekly period.
   *
   * @covers ::normalize
   */
  public function testNormalizeWeeklyToYearly(): void {
    $item = $this->createMockItem([
      'amount' => 100,
      'period' => 'week',
      'unit' => '/m²',
    ]);

    $result = $this->normalizer->normalize($item);
    $this->assertEquals(5200, $result);
  }

  /**
   * Tests normalization with global price to per m².
   *
   * @covers ::normalize
   */
  public function testNormalizeGlobalToPerM2(): void {
    $item = $this->createMockItem([
      'amount' => 10000,
      'period' => 'year',
      'unit' => '/year',
    ]);

    $result = $this->normalizer->normalize($item, 100.0);
    $this->assertEquals(100, $result);
  }

  /**
   * Tests normalization with on_request.
   *
   * @covers ::normalize
   */
  public function testNormalizeOnRequest(): void {
    $item = $this->createMockItem([
      'amount' => 1000,
      'is_on_request' => TRUE,
    ]);

    $result = $this->normalizer->normalize($item);
    $this->assertNull($result);
  }

  /**
   * Tests normalization with null amount.
   *
   * @covers ::normalize
   */
  public function testNormalizeNullAmount(): void {
    $item = $this->createMockItem([
      'amount' => NULL,
    ]);

    $result = $this->normalizer->normalize($item);
    $this->assertNull($result);
  }

  /**
   * Creates a mock field item.
   *
   * @param array $values
   *   The field values.
   *
   * @return \Drupal\Core\Field\FieldItemInterface
   *   The mock field item.
   */
  private function createMockItem(array $values): FieldItemInterface {
    $defaults = [
      'amount' => NULL,
      'unit' => '/m²',
      'period' => 'year',
      'is_on_request' => FALSE,
    ];

    $values += $defaults;

    $mock = $this->getMockBuilder(FieldItemInterface::class)
      ->disableOriginalConstructor()
      ->getMock();

    // Configure each property access.
    foreach ($values as $key => $value) {
      $mock->$key = $value;
    }

    $mock->method('__get')->willReturnCallback(function ($key) use ($values) {
      return $values[$key] ?? NULL;
    });

    return $mock;
  }

}
