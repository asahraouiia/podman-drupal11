<?php

declare(strict_types=1);

namespace Drupal\Tests\ps_features\Functional;

use Drupal\Tests\BrowserTestBase;
use Drupal\language\Entity\ConfigurableLanguage;

/**
 * Tests default language is used for canonical fields.
 *
 * @group ps_features
 */
class DefaultLanguageTest extends BrowserTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'system',
    'user',
    'language',
    'ps',
    'ps_dictionary',
    'ps_features',
  ];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * Tests canonical label uses default language.
   */
  public function testCanonicalLabelUsesDefaultLanguage(): void {
    // Add French and set as default.
    ConfigurableLanguage::createFromLangcode('fr')->save();
    $this->config('system.site')->set('default_langcode', 'fr')->save();
    $this->container->get('language_manager')->reset();

    // Import CSV with en and fr translations.
    $csv = "id,label_en,label_fr,description_en,description_fr,value_type,group,weight,is_required,unit,dictionary_type,validation_min,validation_max,icon\n";
    $csv .= "french_default,English Label,Étiquette française,English description,Description française,flag,test,0,FALSE,,,,,";

    $manager = $this->container->get('ps_features.import_export');
    $report = $manager->import($csv, 'csv');

    $this->assertTrue($report['success']);

    // Load and verify canonical fields use French.
    $storage = $this->container->get('entity_type.manager')->getStorage('ps_feature');
    $feature = $storage->load('french_default');

    $this->assertNotNull($feature);
    $this->assertEquals('Étiquette française', $feature->label());
    $this->assertEquals('Description française', $feature->getDescription());
  }

  /**
   * Tests changing default language and reimporting updates canonical fields.
   */
  public function testChangingDefaultLanguageUpdatesCanonicalFields(): void {
    // Start with English default.
    ConfigurableLanguage::createFromLangcode('fr')->save();
    ConfigurableLanguage::createFromLangcode('de')->save();

    $csv = "id,label_en,label_fr,label_de,description_en,description_fr,description_de,value_type,group,weight,is_required,unit,dictionary_type,validation_min,validation_max,icon\n";
    $csv .= "lang_switch,English,Français,Deutsch,Desc EN,Desc FR,Desc DE,numeric,test,0,FALSE,m,,,,";

    $manager = $this->container->get('ps_features.import_export');
    $manager->import($csv, 'csv');

    $storage = $this->container->get('entity_type.manager')->getStorage('ps_feature');
    $feature = $storage->load('lang_switch');

    // Should be English initially.
    $this->assertEquals('English', $feature->label());

    // Change default language to German.
    $this->config('system.site')->set('default_langcode', 'de')->save();
    $this->container->get('language_manager')->reset();

    // Re-import with update mode.
    $manager->import($csv, 'csv', ['mode' => 'update-only']);

    // Reload feature.
    $storage->resetCache(['lang_switch']);
    $feature = $storage->load('lang_switch');

    // Should now be German.
    $this->assertEquals('Deutsch', $feature->label());
    $this->assertEquals('Desc DE', $feature->getDescription());
  }

  /**
   * Tests fallback to English if default language translation missing.
   */
  public function testFallbackToEnglishIfDefaultMissing(): void {
    // Set French as default but don't provide French translation.
    ConfigurableLanguage::createFromLangcode('fr')->save();
    $this->config('system.site')->set('default_langcode', 'fr')->save();
    $this->container->get('language_manager')->reset();

    $csv = "id,label_en,label_fr,description_en,description_fr,value_type,group,weight,is_required,unit,dictionary_type,validation_min,validation_max,icon\n";
    $csv .= "fallback_test,English Only,,English description,,flag,test,0,FALSE,,,,,";

    $manager = $this->container->get('ps_features.import_export');
    $manager->import($csv, 'csv');

    $storage = $this->container->get('entity_type.manager')->getStorage('ps_feature');
    $feature = $storage->load('fallback_test');

    // Should fallback to English.
    $this->assertEquals('English Only', $feature->label());
  }

}
