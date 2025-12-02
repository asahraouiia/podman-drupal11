<?php

declare(strict_types=1);

namespace Drupal\Tests\ps_diagnostic\Unit\Service;

use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\Logger\LoggerChannelInterface;
use Drupal\ps_diagnostic\Service\CompareBuilder;
use Drupal\ps_diagnostic\Service\DiagnosticNormalizerInterface;
use Drupal\Tests\UnitTestCase;

/**
 * Tests for CompareBuilder service.
 *
 * @group ps_diagnostic
 * @coversDefaultClass \Drupal\ps_diagnostic\Service\CompareBuilder
 */
final class CompareBuilderTest extends UnitTestCase {

  /**
   * The compare builder under test.
   */
  private CompareBuilder $builder;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $normalizer = $this->createMock(DiagnosticNormalizerInterface::class);

    $loggerChannel = $this->createMock(LoggerChannelInterface::class);
    $loggerFactory = $this->createMock(LoggerChannelFactoryInterface::class);
    $loggerFactory->method('get')->willReturn($loggerChannel);

    $this->builder = new CompareBuilder($normalizer, $loggerFactory);
  }

  /**
   * @covers ::buildCompareData
   */
  public function testBuildCompareDataComplete(): void {
    $diagnostics = [
      [
        'type_id' => 'dpe',
        'label_code' => 'B',
        'value_numeric' => 120.5,
        'valid_from' => '2024-01-01',
        'valid_to' => '2034-01-01',
        'completeness_score' => 85,
      ],
      [
        'type_id' => 'ges',
        'label_code' => 'A',
        'value_numeric' => 10.2,
        'completeness_score' => 92,
      ],
    ];

    $result = $this->builder->buildCompareData($diagnostics);

    $this->assertIsArray($result);
    $this->assertEquals(['B', 'A'], $result['energy']);
    $this->assertCount(2, $result['values']);
    $this->assertEquals(92, $result['completeness']);
    $this->assertCount(1, $result['validity']);
  }

  /**
   * @covers ::buildCompareData
   */
  public function testBuildCompareDataEmpty(): void {
    $result = $this->builder->buildCompareData([]);

    $this->assertIsArray($result);
    $this->assertEmpty($result['energy']);
    $this->assertEmpty($result['values']);
    $this->assertEquals(0, $result['completeness']);
    $this->assertEmpty($result['validity']);
  }

  /**
   * @covers ::buildCompareData
   */
  public function testBuildCompareDataValues(): void {
    $diagnostics = [
      [
        'type_id' => 'dpe',
        'value_numeric' => 120.5,
      ],
    ];

    $result = $this->builder->buildCompareData($diagnostics);

    $this->assertCount(1, $result['values']);
    $this->assertEquals(120.5, $result['values'][0]['value']);
    $this->assertEquals('dpe', $result['values'][0]['type_id']);
  }

}
