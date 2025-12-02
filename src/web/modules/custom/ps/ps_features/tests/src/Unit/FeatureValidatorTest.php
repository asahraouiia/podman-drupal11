<?php

declare(strict_types=1);

namespace Drupal\Tests\ps_features\Unit;

use Drupal\ps_dictionary\Service\DictionaryManagerInterface;
use Drupal\ps_features\Entity\FeatureInterface;
use Drupal\ps_features\Service\FeatureValidator;
use Drupal\Tests\UnitTestCase;

/**
 * Tests for FeatureValidator service.
 *
 * @coversDefaultClass \Drupal\ps_features\Service\FeatureValidator
 * @group ps_features
 */
final class FeatureValidatorTest extends UnitTestCase {

  /**
   * The feature validator.
   */
  private FeatureValidator $validator;

  /**
   * The mocked dictionary manager.
   */
  private DictionaryManagerInterface $dictionaryManager;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->dictionaryManager = $this->createMock(DictionaryManagerInterface::class);
    $stringTranslation = $this->getStringTranslationStub();
    $this->validator = new FeatureValidator($this->dictionaryManager, $stringTranslation);
  }

  /**
   * @covers ::validateNumeric
   */
  public function testValidateNumericValid(): void {
    $feature = $this->createMock(FeatureInterface::class);
    $feature->method('isRequired')->willReturn(FALSE);

    $value = ['value_numeric' => 42];
    $errors = $this->validator->validateNumeric($feature, $value);

    $this->assertEmpty($errors);
  }

  /**
   * @covers ::validateNumeric
   */
  public function testValidateNumericInvalid(): void {
    $feature = $this->createMock(FeatureInterface::class);
    $feature->method('label')->willReturn('Test Feature');

    $value = ['value_numeric' => 'not_a_number'];
    $errors = $this->validator->validateNumeric($feature, $value);

    $this->assertNotEmpty($errors);
  }

  /**
   * @covers ::validateString
   */
  public function testValidateStringValid(): void {
    $feature = $this->createMock(FeatureInterface::class);
    $feature->method('isRequired')->willReturn(FALSE);

    $value = ['value_string' => 'test string'];
    $errors = $this->validator->validateString($feature, $value);

    $this->assertEmpty($errors);
  }

  /**
   * @covers ::validateRange
   */
  public function testValidateRangeValid(): void {
    $feature = $this->createMock(FeatureInterface::class);
    $feature->method('isRequired')->willReturn(FALSE);

    $value = [
      'value_range_min' => 10,
      'value_range_max' => 20,
    ];
    $errors = $this->validator->validateRange($feature, $value);

    $this->assertEmpty($errors);
  }

  /**
   * @covers ::validateRange
   */
  public function testValidateRangeMinGreaterThanMax(): void {
    $feature = $this->createMock(FeatureInterface::class);
    $feature->method('label')->willReturn('Test Feature');

    $value = [
      'value_range_min' => 20,
      'value_range_max' => 10,
    ];
    $errors = $this->validator->validateRange($feature, $value);

    $this->assertNotEmpty($errors);
  }

  /**
   * @covers ::validateFlag
   */
  public function testValidateFlagValid(): void {
    $feature = $this->createMock(FeatureInterface::class);
    $feature->method('isRequired')->willReturn(FALSE);

    $value = ['value_boolean' => TRUE];
    $errors = $this->validator->validateFlag($feature, $value);

    $this->assertEmpty($errors);
  }

  /**
   * @covers ::validateYesNo
   */
  public function testValidateYesNoValid(): void {
    $feature = $this->createMock(FeatureInterface::class);
    $feature->method('isRequired')->willReturn(FALSE);

    $this->dictionaryManager->method('isValid')
      ->with('yesno', 'yes')
      ->willReturn(TRUE);

    $value = ['value_dictionary' => 'yes'];
    $errors = $this->validator->validateYesNo($feature, $value);

    $this->assertEmpty($errors);
  }

  /**
   * @covers ::validateDictionary
   */
  public function testValidateDictionaryValid(): void {
    $feature = $this->createMock(FeatureInterface::class);
    $feature->method('isRequired')->willReturn(FALSE);
    $feature->method('getDictionaryType')->willReturn('heating_type');

    $this->dictionaryManager->method('isValid')
      ->with('heating_type', 'electric')
      ->willReturn(TRUE);

    $value = ['value_dictionary' => 'electric'];
    $errors = $this->validator->validateDictionary($feature, $value);

    $this->assertEmpty($errors);
  }

  /**
   * @covers ::validate
   */
  public function testValidateDispatchesToCorrectValidator(): void {
    $feature = $this->createMock(FeatureInterface::class);
    $feature->method('getValueType')->willReturn('flag');
    $feature->method('isRequired')->willReturn(FALSE);

    $value = ['value_boolean' => TRUE];
    $errors = $this->validator->validate($feature, $value);

    $this->assertIsArray($errors);
    $this->assertEmpty($errors);
  }

}
