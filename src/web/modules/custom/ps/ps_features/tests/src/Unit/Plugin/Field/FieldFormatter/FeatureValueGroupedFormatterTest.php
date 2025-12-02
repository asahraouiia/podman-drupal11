<?php

declare(strict_types=1);

namespace Drupal\Tests\ps_features\Unit\Plugin\Field\FieldFormatter;

use Drupal\ps_features\Entity\FeatureInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\ps_dictionary\Service\DictionaryManagerInterface;
use Drupal\ps_features\Plugin\Field\FieldFormatter\FeatureValueGroupedFormatter;
use Drupal\ps_features\Service\FeatureManagerInterface;
use Drupal\Tests\UnitTestCase;

/**
 * Tests the FeatureValueGroupedFormatter.
 *
 * @coversDefaultClass \Drupal\ps_features\Plugin\Field\FieldFormatter\FeatureValueGroupedFormatter
 * @group ps_features
 */
class FeatureValueGroupedFormatterTest extends UnitTestCase {

  /**
   * The formatter instance.
   */
  protected FeatureValueGroupedFormatter $formatter;

  /**
   * Mock feature manager.
   */
  protected FeatureManagerInterface $featureManager;

  /**
   * Mock dictionary manager.
   */
  protected DictionaryManagerInterface $dictionaryManager;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->featureManager = $this->createMock(FeatureManagerInterface::class);
    $this->dictionaryManager = $this->createMock(DictionaryManagerInterface::class);
    $entityTypeManager = $this->createMock(EntityTypeManagerInterface::class);

    $field_definition = $this->createMock(FieldDefinitionInterface::class);

    // Mock string translation.
    $string_translation = $this->getStringTranslationStub();

    $this->formatter = new FeatureValueGroupedFormatter(
      'ps_feature_value_grouped',
      [],
      $field_definition,
      [
        'show_empty_groups' => FALSE,
        'collapsible_groups' => TRUE,
        'collapsed_by_default' => FALSE,
        'show_icons' => TRUE,
        'show_units' => TRUE,
      ],
      'above',
      'default',
      [],
      $this->featureManager,
      $this->dictionaryManager,
      $entityTypeManager
    );

    $this->formatter->setStringTranslation($string_translation);
  }

  /**
   * Tests default settings.
   *
   * @covers ::defaultSettings
   */
  public function testDefaultSettings(): void {
    $settings = FeatureValueGroupedFormatter::defaultSettings();

    $this->assertArrayHasKey('show_empty_groups', $settings);
    $this->assertArrayHasKey('collapsible_groups', $settings);
    $this->assertArrayHasKey('collapsed_by_default', $settings);
    $this->assertArrayHasKey('show_icons', $settings);
    $this->assertArrayHasKey('show_units', $settings);
    $this->assertFalse($settings['show_empty_groups']);
    $this->assertTrue($settings['collapsible_groups']);
    $this->assertFalse($settings['collapsed_by_default']);
    $this->assertTrue($settings['show_icons']);
    $this->assertTrue($settings['show_units']);
  }

  /**
   * Tests settings form.
   *
   * @covers ::settingsForm
   */
  public function testSettingsForm(): void {
    $form = [];
    $form_state = $this->createMock(FormStateInterface::class);

    $elements = $this->formatter->settingsForm($form, $form_state);

    $this->assertArrayHasKey('show_empty_groups', $elements);
    $this->assertArrayHasKey('collapsible_groups', $elements);
    $this->assertArrayHasKey('collapsed_by_default', $elements);
    $this->assertArrayHasKey('show_icons', $elements);
    $this->assertArrayHasKey('show_units', $elements);
  }

  /**
   * Tests settings summary.
   *
   * @covers ::settingsSummary
   */
  public function testSettingsSummary(): void {
    $summary = $this->formatter->settingsSummary();

    $this->assertIsArray($summary);
    $this->assertCount(6, $summary);
    $this->assertStringContainsString('empty groups', (string) $summary[0]);
    $this->assertStringContainsString('Collapsible', (string) $summary[1]);
    $this->assertStringContainsString('icons', (string) $summary[2]);
    $this->assertStringContainsString('units', (string) $summary[3]);
    $this->assertStringContainsString('weight', (string) $summary[4]);
    $this->assertStringContainsString('duplicates', (string) $summary[5]);
  }

  /**
   * Tests getDisplayType method.
   *
   * @covers ::getDisplayType
   */
  public function testGetDisplayType(): void {
    // Mock feature for flag type.
    $flagFeature = $this->createMock(FeatureInterface::class);
    $flagFeature->method('getValueType')->willReturn('flag');

    // Mock feature for boolean type.
    $booleanFeature = $this->createMock(FeatureInterface::class);
    $booleanFeature->method('getValueType')->willReturn('boolean');

    // Mock feature for dictionary type.
    $dictionaryFeature = $this->createMock(FeatureInterface::class);
    $dictionaryFeature->method('getValueType')->willReturn('dictionary');

    // Mock feature for string type.
    $stringFeature = $this->createMock(FeatureInterface::class);
    $stringFeature->method('getValueType')->willReturn('string');

    $reflection = new \ReflectionClass($this->formatter);
    $method = $reflection->getMethod('getDisplayType');
    $method->setAccessible(TRUE);

    // Test label-only (flag always returns label-only).
    $item = (object) ['value_boolean' => TRUE, 'value_string' => ''];
    $result = $method->invoke($this->formatter, $item, $flagFeature);
    $this->assertEquals('label-only', $result);

    // Test boolean-custom (boolean with custom text).
    $item = (object) ['value_boolean' => TRUE, 'value_string' => 'Reversible'];
    $result = $method->invoke($this->formatter, $item, $booleanFeature);
    $this->assertEquals('boolean-custom', $result);

    // Test label-only (boolean without custom text).
    $item = (object) ['value_boolean' => TRUE, 'value_string' => ''];
    $result = $method->invoke($this->formatter, $item, $booleanFeature);
    $this->assertEquals('label-only', $result);

    // Test dictionary.
    $item = (object) ['value_string' => 'individual'];
    $result = $method->invoke($this->formatter, $item, $dictionaryFeature);
    $this->assertEquals('dictionary', $result);

    // Test label-value.
    $item = (object) ['value_string' => 'Optical Fiber'];
    $result = $method->invoke($this->formatter, $item, $stringFeature);
    $this->assertEquals('label-value', $result);
  }

}
