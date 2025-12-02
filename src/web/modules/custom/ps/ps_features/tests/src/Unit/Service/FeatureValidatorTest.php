<?php

declare(strict_types=1);

namespace Drupal\Tests\ps_features\Unit\Service;

use Drupal\ps_dictionary\Service\DictionaryManagerInterface;
use Drupal\ps_features\Entity\FeatureInterface;
use Drupal\ps_features\Service\FeatureValidator;
use Drupal\Tests\UnitTestCase;

/**
 * @coversDefaultClass \Drupal\ps_features\Service\FeatureValidator
 * @group ps_features
 */
final class FeatureValidatorTest extends UnitTestCase {

  /**
   * The dictionary manager mock.
   */
  private DictionaryManagerInterface $dictionaryManager;

  /**
   * The feature validator instance.
   */
  private FeatureValidator $validator;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->dictionaryManager = $this->createMock(DictionaryManagerInterface::class);

    // Mock string translation.
    $stringTranslation = $this->getStringTranslationStub();

    $this->validator = new FeatureValidator(
      $this->dictionaryManager,
      $stringTranslation
    );
  }

  /**
   * @covers ::validateNumeric
   */
  public function testValidateNumericValid(): void {
    $feature = $this->createMock(FeatureInterface::class);
    $feature->method('isRequired')->willReturn(FALSE);
    $feature->method('getValidationRules')->willReturn([
      'min' => 0,
      'max' => 100,
    ]);

    $value = ['value_numeric' => 50];

    $errors = $this->validator->validateNumeric($feature, $value);

    $this->assertEmpty($errors);
  }

  /**
   * @covers ::validateNumeric
   */
  public function testValidateNumericOutOfRange(): void {
    $feature = $this->createMock(FeatureInterface::class);
    $feature->method('isRequired')->willReturn(FALSE);
    $feature->method('getValidationRules')->willReturn([
      'min' => 0,
      'max' => 100,
    ]);

    $value = ['value_numeric' => 150];

    $errors = $this->validator->validateNumeric($feature, $value);

    $this->assertNotEmpty($errors);
  }

  /**
   * @covers ::validateRange
   */
  public function testValidateRangeValid(): void {
    $feature = $this->createMock(FeatureInterface::class);
    $feature->method('isRequired')->willReturn(FALSE);
    $feature->method('getValidationRules')->willReturn([]);

    $value = [
      'value_range_min' => 3.5,
      'value_range_max' => 5.2,
    ];

    $errors = $this->validator->validateRange($feature, $value);

    $this->assertEmpty($errors);
  }

  /**
   * @covers ::validateRange
   */
  public function testValidateRangeInvalid(): void {
    $feature = $this->createMock(FeatureInterface::class);
    $feature->method('isRequired')->willReturn(FALSE);
    $feature->method('getValidationRules')->willReturn([]);

    $value = [
      'value_range_min' => 10,
      'value_range_max' => 5,
    ];

    $errors = $this->validator->validateRange($feature, $value);

    $this->assertNotEmpty($errors);
    $this->assertStringContainsString('cannot be greater', $errors[0]);
  }

  /**
   * @covers ::validateDictionary
   */
  public function testValidateDictionaryValid(): void {
    $feature = $this->createMock(FeatureInterface::class);
    $feature->method('getDictionaryType')->willReturn('air_conditioning_type');
    $feature->method('isRequired')->willReturn(FALSE);

    $this->dictionaryManager
      ->method('isValid')
      ->with('air_conditioning_type', 'reversible')
      ->willReturn(TRUE);

    $value = ['value_string' => 'reversible'];

    $errors = $this->validator->validateDictionary($feature, $value);

    $this->assertEmpty($errors);
  }

  /**
   * @covers ::validateDictionary
   */
  public function testValidateDictionaryInvalidCode(): void {
    $feature = $this->createMock(FeatureInterface::class);
    $feature->method('getDictionaryType')->willReturn('air_conditioning_type');
    $feature->method('isRequired')->willReturn(FALSE);

    $this->dictionaryManager
      ->method('isValid')
      ->with('air_conditioning_type', 'invalid')
      ->willReturn(FALSE);

    $value = ['value_string' => 'invalid'];

    $errors = $this->validator->validateDictionary($feature, $value);

    $this->assertNotEmpty($errors);
    $this->assertStringContainsString('Invalid dictionary code', $errors[0]);
  }

  /**
   * @covers ::validateString
   */
  public function testValidateStringValid(): void {
    $feature = $this->createMock(FeatureInterface::class);
    $feature->method('isRequired')->willReturn(FALSE);
    $feature->method('getValidationRules')->willReturn([]);

    $value = ['value_string' => 'Some text'];

    $errors = $this->validator->validateString($feature, $value);

    $this->assertEmpty($errors);
  }

}
