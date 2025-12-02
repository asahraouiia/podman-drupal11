<?php

declare(strict_types=1);

namespace Drupal\Tests\ps_price\Unit\Service;

use Drupal\Core\Field\FieldItemInterface;
use Drupal\Core\Language\Language;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\ps_price\Service\PriceFormatter;
use PHPUnit\Framework\TestCase;

/**
 * Tests for PriceFormatter service.
 *
 * @coversDefaultClass \Drupal\ps_price\Service\PriceFormatter
 * @group ps_price
 */
class PriceFormatterTest extends TestCase {

  /**
   * The price formatter service.
   *
   * @var \Drupal\ps_price\Service\PriceFormatter
   */
  private PriceFormatter $formatter;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $languageManager = $this->createMock(LanguageManagerInterface::class);
    $language = new Language(['id' => 'en']);
    $languageManager->method('getCurrentLanguage')->willReturn($language);

    $this->formatter = new PriceFormatter($languageManager);

    // Mock string translation.
    $stringTranslation = $this->createMock('Drupal\Core\StringTranslation\TranslationInterface');
    $stringTranslation->method('translate')->willReturnArgument(0);
    $this->formatter->setStringTranslation($stringTranslation);
  }

  /**
   * Tests formatting with "on request" flag.
   *
   * @covers ::format
   */
  public function testFormatOnRequest(): void {
    $item = $this->createMockItem([
      'amount' => 1000,
      'is_on_request' => TRUE,
    ]);

    $result = $this->formatter->format($item);
    $this->assertEquals('On request', $result);
  }

  /**
   * Tests basic price formatting.
   *
   * @covers ::format
   */
  public function testFormatBasic(): void {
    $item = $this->createMockItem([
      'amount' => 1250.50,
      'currency' => 'EUR',
      'unit' => '/m²/an',
      'period' => 'year',
    ]);

    $result = $this->formatter->format($item);
    $this->assertStringContainsString('1,250.50', $result);
    $this->assertStringContainsString('EUR', $result);
  }

  /**
   * Tests price range formatting.
   *
   * @covers ::format
   */
  public function testFormatRange(): void {
    $item = $this->createMockItem([
      'amount' => 1000,
      'amount_to' => 1500,
      'currency' => 'EUR',
    ]);

    $result = $this->formatter->format($item);
    $this->assertStringContainsString('1,000.00', $result);
    $this->assertStringContainsString('1,500.00', $result);
    $this->assertStringContainsString('-', $result);
  }

  /**
   * Tests formatting with flags.
   *
   * @covers ::format
   */
  public function testFormatWithFlags(): void {
    $item = $this->createMockItem([
      'amount' => 1000,
      'currency' => 'EUR',
      'is_from' => TRUE,
      'is_vat_excluded' => TRUE,
      'is_charges_included' => TRUE,
    ]);

    $result = $this->formatter->format($item);
    $this->assertStringContainsString('From', $result);
    $this->assertStringContainsString('excl. VAT', $result);
    $this->assertStringContainsString('charges incl.', $result);
  }

  /**
   * Tests short format.
   *
   * @covers ::formatShort
   */
  public function testFormatShort(): void {
    $item = $this->createMockItem([
      'amount' => 1250.50,
      'currency' => 'EUR',
      'unit' => '/m²/an',
    ]);

    $result = $this->formatter->formatShort($item);
    $this->assertStringContainsString('1,250.50', $result);
    $this->assertStringContainsString('EUR', $result);
    $this->assertStringNotContainsString('/m²', $result);
  }

  /**
   * Tests numeric value for search.
   *
   * @covers ::getNumericForSearch
   */
  public function testGetNumericForSearch(): void {
    $item = $this->createMockItem([
      'amount' => 1250.50,
    ]);

    $result = $this->formatter->getNumericForSearch($item);
    $this->assertEquals(1250.50, $result);
  }

  /**
   * Tests numeric value for search with on_request.
   *
   * @covers ::getNumericForSearch
   */
  public function testGetNumericForSearchOnRequest(): void {
    $item = $this->createMockItem([
      'amount' => 1000,
      'is_on_request' => TRUE,
    ]);

    $result = $this->formatter->getNumericForSearch($item);
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
      'amount_to' => NULL,
      'currency' => 'EUR',
      'unit' => NULL,
      'period' => 'year',
      'is_on_request' => FALSE,
      'is_from' => FALSE,
      'is_vat_excluded' => FALSE,
      'is_charges_included' => FALSE,
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
