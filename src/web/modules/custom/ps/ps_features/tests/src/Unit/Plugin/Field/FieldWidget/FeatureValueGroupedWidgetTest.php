<?php

declare(strict_types=1);

namespace Drupal\Tests\ps_features\Unit\Plugin\Field\FieldWidget;

use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\RendererInterface;
use Drupal\ps_dictionary\Service\DictionaryManagerInterface;
use Drupal\ps_features\Plugin\Field\FieldWidget\FeatureValueGroupedWidget;
use Drupal\ps_features\Service\FeatureManagerInterface;
use Drupal\Tests\UnitTestCase;

/**
 * Tests the FeatureValueGroupedWidget.
 *
 * @coversDefaultClass \Drupal\ps_features\Plugin\Field\FieldWidget\FeatureValueGroupedWidget
 * @group ps_features
 */
class FeatureValueGroupedWidgetTest extends UnitTestCase {

  /**
   * The widget instance.
   */
  protected FeatureValueGroupedWidget $widget;

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
    $renderer = $this->createMock(RendererInterface::class);

    $field_definition = $this->createMock(FieldDefinitionInterface::class);

    // Mock string translation.
    $string_translation = $this->getStringTranslationStub();

    $this->widget = new FeatureValueGroupedWidget(
      'ps_feature_value_grouped',
      [],
      $field_definition,
      ['show_descriptions' => TRUE, 'allow_reorder' => TRUE, 'collapsed_groups' => FALSE],
      [],
      $this->featureManager,
      $this->dictionaryManager,
      $renderer
    );

    $this->widget->setStringTranslation($string_translation);
  }

  /**
   * Tests default settings.
   *
   * @covers ::defaultSettings
   */
  public function testDefaultSettings(): void {
    $settings = FeatureValueGroupedWidget::defaultSettings();

    $this->assertArrayHasKey('show_descriptions', $settings);
    $this->assertArrayHasKey('allow_reorder', $settings);
    $this->assertArrayHasKey('collapsed_groups', $settings);
    $this->assertTrue($settings['show_descriptions']);
    $this->assertTrue($settings['allow_reorder']);
    $this->assertFalse($settings['collapsed_groups']);
  }

  /**
   * Tests settings form.
   *
   * @covers ::settingsForm
   */
  public function testSettingsForm(): void {
    $form = [];
    $form_state = $this->createMock(FormStateInterface::class);

    $elements = $this->widget->settingsForm($form, $form_state);

    $this->assertArrayHasKey('show_descriptions', $elements);
    $this->assertArrayHasKey('allow_reorder', $elements);
    $this->assertArrayHasKey('collapsed_groups', $elements);
    $this->assertEquals('checkbox', $elements['show_descriptions']['#type']);
    $this->assertEquals('checkbox', $elements['allow_reorder']['#type']);
    $this->assertEquals('checkbox', $elements['collapsed_groups']['#type']);
  }

  /**
   * Tests settings summary.
   *
   * @covers ::settingsSummary
   */
  public function testSettingsSummary(): void {
    $summary = $this->widget->settingsSummary();

    $this->assertIsArray($summary);
    $this->assertCount(3, $summary);
    $this->assertStringContainsString('Show descriptions', (string) $summary[0]);
    $this->assertStringContainsString('Reordering', (string) $summary[1]);
    $this->assertStringContainsString('Groups', (string) $summary[2]);
  }

  /**
   * Tests massageFormValues decodes JSON data.
   *
   * @covers ::massageFormValues
   */
  public function testMassageFormValues(): void {
    // Widget stores JSON in hidden field 'data'.
    $json_data = json_encode([
      [
        'feature_id' => 'air_conditioning',
        'feature_type' => 'yesno',
        'config' => ['value' => 'yes'],
      ],
      [
        'feature_id' => 'heating',
        'feature_type' => 'yesno',
        'config' => ['value' => 'yes'],
      ],
      [
        'feature_id' => 'internet',
        'feature_type' => 'flag',
        'config' => [],
      ],
    ]);

    $values = [
      ['data' => $json_data],
    ];

    $form = [];
    $form_state = $this->createMock(FormStateInterface::class);

    $result = $this->widget->massageFormValues($values, $form, $form_state);

    $this->assertCount(3, $result);
    $this->assertEquals('air_conditioning', $result[0]['feature_id']);
    $this->assertEquals('heating', $result[1]['feature_id']);
    $this->assertEquals('internet', $result[2]['feature_id']);
  }

}
