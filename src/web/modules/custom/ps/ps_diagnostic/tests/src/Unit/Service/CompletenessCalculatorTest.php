<?php

declare(strict_types=1);

namespace Drupal\Tests\ps_diagnostic\Unit\Service;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Config\ImmutableConfig;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\Logger\LoggerChannelInterface;
use Drupal\ps_diagnostic\Service\CompletenessCalculator;
use Drupal\Tests\UnitTestCase;

/**
 * Tests for CompletenessCalculator service.
 *
 * @group ps_diagnostic
 * @coversDefaultClass \Drupal\ps_diagnostic\Service\CompletenessCalculator
 */
final class CompletenessCalculatorTest extends UnitTestCase {

  /**
   * The completeness calculator under test.
   */
  private CompletenessCalculator $calculator;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $config = $this->createMock(ImmutableConfig::class);
    $config->method('get')->willReturnMap([
      ['enable_completeness_score', TRUE],
    ]);

    $configFactory = $this->createMock(ConfigFactoryInterface::class);
    $configFactory->method('get')->willReturn($config);

    $loggerChannel = $this->createMock(LoggerChannelInterface::class);
    $loggerFactory = $this->createMock(LoggerChannelFactoryInterface::class);
    $loggerFactory->method('get')->willReturn($loggerChannel);

    $this->calculator = new CompletenessCalculator(
      $configFactory,
      $loggerFactory
    );
  }

  /**
   * @covers ::calculateScore
   */
  public function testCalculateScoreComplete(): void {
    $data = [
      'type_id' => 'dpe',
      'value_numeric' => 120.5,
      'label_code' => 'B',
      'valid_from' => '2024-01-01',
      'valid_to' => '2034-01-01',
    ];

    $score = $this->calculator->calculateScore($data);

    $this->assertEquals(100, $score);
  }

  /**
   * @covers ::calculateScore
   */
  public function testCalculateScorePartial(): void {
    $data = [
      'type_id' => 'dpe',
      'label_code' => 'B',
    ];

    $score = $this->calculator->calculateScore($data);

    // type_id (30) + label_code (20) = 50%.
    $this->assertEquals(50, $score);
  }

  /**
   * @covers ::calculateScore
   */
  public function testCalculateScoreEmpty(): void {
    $data = [];

    $score = $this->calculator->calculateScore($data);

    $this->assertEquals(0, $score);
  }

  /**
   * @covers ::calculateScore
   */
  public function testCalculateScoreDisabled(): void {
    $config = $this->createMock(ImmutableConfig::class);
    $config->method('get')->willReturnMap([
      ['enable_completeness_score', FALSE],
    ]);

    $configFactory = $this->createMock(ConfigFactoryInterface::class);
    $configFactory->method('get')->willReturn($config);

    $loggerChannel = $this->createMock(LoggerChannelInterface::class);
    $loggerFactory = $this->createMock(LoggerChannelFactoryInterface::class);
    $loggerFactory->method('get')->willReturn($loggerChannel);

    $calculator = new CompletenessCalculator($configFactory, $loggerFactory);

    $data = ['type_id' => 'dpe', 'label_code' => 'B'];

    $score = $calculator->calculateScore($data);

    $this->assertEquals(0, $score);
  }

  /**
   * @covers ::getFieldWeights
   */
  public function testGetFieldWeights(): void {
    $weights = $this->calculator->getFieldWeights();

    $this->assertIsArray($weights);
    $this->assertArrayHasKey('type_id', $weights);
    $this->assertArrayHasKey('label_code', $weights);
    $this->assertEquals(30, $weights['type_id']);
    $this->assertEquals(20, $weights['label_code']);
  }

}
