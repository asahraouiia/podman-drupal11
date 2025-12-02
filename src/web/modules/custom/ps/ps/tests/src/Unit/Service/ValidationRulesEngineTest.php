<?php

declare(strict_types=1);

namespace Drupal\Tests\ps\Unit\Service;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Config\ImmutableConfig;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\Logger\LoggerChannelInterface;
use Drupal\ps\Service\ValidationRulesEngine;
use Drupal\Tests\UnitTestCase;

/**
 * Tests ValidationRulesEngine service.
 *
 * @coversDefaultClass \Drupal\ps\Service\ValidationRulesEngine
 * @group ps
 */
class ValidationRulesEngineTest extends UnitTestCase {

  /**
   * The validation rules engine.
   *
   * @var \Drupal\ps\Service\ValidationRulesEngine
   */
  protected $validationEngine;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $configFactory = $this->createMock(ConfigFactoryInterface::class);
    $config = $this->createMock(ImmutableConfig::class);
    $config->method('get')
      ->willReturn(FALSE);

    $configFactory->method('get')
      ->willReturn($config);

    $loggerFactory = $this->createMock(LoggerChannelFactoryInterface::class);
    $logger = $this->createMock(LoggerChannelInterface::class);
    $loggerFactory->method('get')
      ->willReturn($logger);

    $this->validationEngine = new ValidationRulesEngine($configFactory, $loggerFactory);
  }

  /**
   * Tests validate method with required field.
   *
   * @covers ::validate
   */
  public function testValidateRequired(): void {
    $data = ['name' => ''];
    $rules = ['name' => ['required' => TRUE]];

    $result = $this->validationEngine->validate($data, $rules);

    $this->assertFalse($result['valid']);
    $this->assertArrayHasKey('name', $result['errors']);
  }

  /**
   * Tests validate method with type checking.
   *
   * @covers ::validate
   */
  public function testValidateType(): void {
    $data = ['age' => 'not a number'];
    $rules = ['age' => ['type' => 'int']];

    $result = $this->validationEngine->validate($data, $rules);

    $this->assertFalse($result['valid']);
    $this->assertArrayHasKey('age', $result['errors']);
  }

  /**
   * Tests validate method with valid data.
   *
   * @covers ::validate
   */
  public function testValidateSuccess(): void {
    $data = ['name' => 'John', 'age' => 30];
    $rules = [
      'name' => ['required' => TRUE, 'type' => 'string'],
      'age' => ['type' => 'int'],
    ];

    $result = $this->validationEngine->validate($data, $rules);

    $this->assertTrue($result['valid']);
    $this->assertEmpty($result['errors']);
  }

  /**
   * Tests addRule method.
   *
   * @covers ::addRule
   */
  public function testAddRule(): void {
    $this->validationEngine->addRule('custom', function ($value) {
      return $value === 'expected';
    });

    $data = ['field' => 'wrong'];
    $rules = ['field' => ['custom' => 'custom']];

    $result = $this->validationEngine->validate($data, $rules);

    $this->assertFalse($result['valid']);
  }

  /**
   * Tests isStrictMode method.
   *
   * @covers ::isStrictMode
   */
  public function testIsStrictMode(): void {
    $this->assertFalse($this->validationEngine->isStrictMode());
  }

}
