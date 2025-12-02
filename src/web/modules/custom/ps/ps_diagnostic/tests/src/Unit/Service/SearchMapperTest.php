<?php

declare(strict_types=1);

namespace Drupal\Tests\ps_diagnostic\Unit\Service;

use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\Logger\LoggerChannelInterface;
use Drupal\ps_diagnostic\Service\DiagnosticNormalizerInterface;
use Drupal\ps_diagnostic\Service\SearchMapper;
use Drupal\Tests\UnitTestCase;

/**
 * Tests for SearchMapper service.
 *
 * @group ps_diagnostic
 * @coversDefaultClass \Drupal\ps_diagnostic\Service\SearchMapper
 */
final class SearchMapperTest extends UnitTestCase {

  /**
   * The search mapper under test.
   */
  private SearchMapper $mapper;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $normalizer = $this->createMock(DiagnosticNormalizerInterface::class);

    $loggerChannel = $this->createMock(LoggerChannelInterface::class);
    $loggerFactory = $this->createMock(LoggerChannelFactoryInterface::class);
    $loggerFactory->method('get')->willReturn($loggerChannel);

    $this->mapper = new SearchMapper($normalizer, $loggerFactory);
  }

  /**
   * @covers ::mapForSearch
   */
  public function testMapForSearchMultipleDiagnostics(): void {
    $diagnostics = [
      [
        'type_id' => 'dpe',
        'label_code' => 'B',
        'value_numeric' => 120.5,
        'completeness_score' => 85,
      ],
      [
        'type_id' => 'ges',
        'label_code' => 'A',
        'value_numeric' => 10.2,
        'completeness_score' => 92,
      ],
    ];

    $result = $this->mapper->mapForSearch($diagnostics);

    $this->assertIsArray($result);
    $this->assertEquals(['dpe', 'ges'], $result['type_ids']);
    $this->assertEquals(['B', 'A'], $result['label_codes']);
    $this->assertEquals([120.5, 10.2], $result['values_numeric']);
    $this->assertEquals(92, $result['max_completeness_score']);
  }

  /**
   * @covers ::mapForSearch
   */
  public function testMapForSearchEmpty(): void {
    $result = $this->mapper->mapForSearch([]);

    $this->assertIsArray($result);
    $this->assertEmpty($result['type_ids']);
    $this->assertEmpty($result['label_codes']);
    $this->assertEmpty($result['values_numeric']);
    $this->assertEquals(0, $result['max_completeness_score']);
  }

  /**
   * @covers ::mapForSearch
   */
  public function testMapForSearchDeduplicatesCodes(): void {
    $diagnostics = [
      ['type_id' => 'dpe', 'label_code' => 'B'],
      ['type_id' => 'dpe', 'label_code' => 'B'],
    ];

    $result = $this->mapper->mapForSearch($diagnostics);

    $this->assertCount(1, $result['type_ids']);
    $this->assertCount(1, $result['label_codes']);
  }

}
