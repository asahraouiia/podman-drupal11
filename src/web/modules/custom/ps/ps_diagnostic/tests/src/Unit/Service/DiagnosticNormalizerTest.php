<?php

declare(strict_types=1);

namespace Drupal\Tests\ps_diagnostic\Unit\Service;

use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\Logger\LoggerChannelInterface;
use Drupal\ps_diagnostic\Service\DiagnosticClassCalculatorInterface;
use Drupal\ps_diagnostic\Service\DiagnosticNormalizer;
use Drupal\Tests\UnitTestCase;

/**
 * Tests for DiagnosticNormalizer service.
 *
 * @group ps_diagnostic
 * @coversDefaultClass \Drupal\ps_diagnostic\Service\DiagnosticNormalizer
 */
final class DiagnosticNormalizerTest extends UnitTestCase {

  /**
   * The diagnostic normalizer under test.
   */
  private DiagnosticNormalizer $normalizer;

  /**
   * Mock class calculator.
   */
  private DiagnosticClassCalculatorInterface $classCalculator;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->classCalculator = $this->createMock(DiagnosticClassCalculatorInterface::class);

    $loggerChannel = $this->createMock(LoggerChannelInterface::class);
    $loggerFactory = $this->createMock(LoggerChannelFactoryInterface::class);
    $loggerFactory->method('get')->willReturn($loggerChannel);

    $this->normalizer = new DiagnosticNormalizer(
      $this->classCalculator,
      $loggerFactory
    );
  }

  /**
   * @covers ::normalize
   */
  public function testNormalizeValidData(): void {
    $data = [
      'type_id' => 'dpe',
      'value_numeric' => 120.5,
      'label_code' => 'B',
      'valid_from' => '2024-01-01',
      'valid_to' => '2034-01-01',
      'no_classification' => FALSE,
      'non_applicable' => FALSE,
    ];

    $result = $this->normalizer->normalize($data);

    $this->assertIsArray($result);
    $this->assertEquals('dpe', $result['type_id']);
    $this->assertEquals(120.5, $result['value_numeric']);
    $this->assertEquals('B', $result['label_code']);
    $this->assertFalse($result['no_classification']);
    $this->assertFalse($result['non_applicable']);
  }

  /**
   * @covers ::normalize
   */
  public function testNormalizeAutoCalculateClass(): void {
    $classCalculator = $this->createMock(DiagnosticClassCalculatorInterface::class);
    $classCalculator->method('calculateClass')->willReturn('D');

    $loggerChannel = $this->createMock(LoggerChannelInterface::class);
    $loggerFactory = $this->createMock(LoggerChannelFactoryInterface::class);
    $loggerFactory->method('get')->willReturn($loggerChannel);

    $normalizer = new DiagnosticNormalizer($classCalculator, $loggerFactory);

    $data = [
      'type_id' => 'dpe',
      'value_numeric' => 200.0,
      'label_code' => NULL,
    ];

    $result = $normalizer->normalize($data);

    $this->assertEquals('D', $result['label_code']);
  }

  /**
   * @covers ::normalize
   */
  public function testNormalizeNegativeValueTruncated(): void {
    $data = ['value_numeric' => -50.0];

    $result = $this->normalizer->normalize($data);

    $this->assertNull($result['value_numeric']);
  }

  /**
   * @covers ::normalize
   */
  public function testNormalizeIncoherentDates(): void {
    $data = [
      'valid_from' => '2024-12-31',
      'valid_to' => '2024-01-01',
    ];

    $result = $this->normalizer->normalize($data);

    $this->assertNull($result['valid_to']);
  }

}
