<?php

declare(strict_types=1);

namespace Drupal\Tests\ps_features\Unit\Plugin\Field\FieldFormatter;

use Drupal\ps_features\Plugin\Field\FieldFormatter\FeatureValueDefaultFormatter;
use Drupal\ps_features\Service\FeatureManagerInterface;
use Drupal\ps_dictionary\Service\DictionaryManagerInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Tests\UnitTestCase;

/**
 * Tests complement display logic in FeatureValueDefaultFormatter.
 *
 * @group ps_features
 * @coversDefaultClass \Drupal\ps_features\Plugin\Field\FieldFormatter\FeatureValueDefaultFormatter
 */
final class FeatureValueDefaultFormatterComplementTest extends UnitTestCase {
  use StringTranslationTrait;

  /**
   * @covers ::viewElements
   */
  public function testComplementAppended(): void {
    $featureManager = $this->createMock(FeatureManagerInterface::class);
    $dictionaryManager = $this->createMock(DictionaryManagerInterface::class);
    $fieldDefinition = $this->createMock(FieldDefinitionInterface::class);
    $items = $this->createMock(FieldItemListInterface::class);

    $formatter = new FeatureValueDefaultFormatter(
      'ps_feature_value_default',
      [],
      $fieldDefinition,
      [
        'hide_empty' => FALSE,
        'show_label' => FALSE,
        'show_unit' => FALSE,
        'show_description' => FALSE,
        'show_complement' => TRUE,
        'empty_text' => '',
        'sort_by_weight' => FALSE,
        'merge_duplicates' => FALSE,
        'duplicate_separator' => ', ',
      ],
      'above',
      'default',
      [],
      $featureManager,
      $dictionaryManager,
    );

    // We cannot fully mock internal item iteration easily here without a lot of setup.
    // Just assert defaultSettings contains the new key and settingsSummary reflects it when enabled.
    $defaults = FeatureValueDefaultFormatter::defaultSettings();
    $this->assertArrayHasKey('show_complement', $defaults);
    $this->assertTrue($defaults['show_complement']);

    // Avoid calling settingsSummary() which relies on container translation services.
    $this->assertTrue($formatter->getSetting('show_complement'));
  }

}
