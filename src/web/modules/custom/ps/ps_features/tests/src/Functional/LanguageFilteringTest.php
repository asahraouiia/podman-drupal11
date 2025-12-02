<?php

declare(strict_types=1);

namespace Drupal\Tests\ps_features\Functional;

use Drupal\Tests\BrowserTestBase;
use Drupal\language\Entity\ConfigurableLanguage;

/**
 * Tests language filtering throughout the import/export cycle.
 *
 * @group ps_features
 */
class LanguageFilteringTest extends BrowserTestBase {

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
   * Tests changing site languages affects export.
   */
  public function testChangingSiteLanguagesAffectsExport(): void {
    // Start with en, fr.
    ConfigurableLanguage::createFromLangcode('fr')->save();

    // Create a feature.
    $storage = $this->container->get('entity_type.manager')->getStorage('ps_feature');
    $feature = $storage->create([
      'id' => 'dynamic_lang',
      'label' => 'Dynamic',
      'description' => 'Test',
      'value_type' => 'flag',
      'metadata' => [
        'group' => 'test',
        'translations' => [
          'en' => ['label' => 'English', 'description' => 'EN'],
          'fr' => ['label' => 'Français', 'description' => 'FR'],
          'de' => ['label' => 'Deutsch', 'description' => 'DE'],
        ],
      ],
    ]);
    $feature->save();

    $manager = $this->container->get('ps_features.import_export');

    // Export with en, fr active.
    $csv1 = $manager->export('csv');
    $this->assertStringContainsString('label_en', $csv1);
    $this->assertStringContainsString('label_fr', $csv1);
    $this->assertStringNotContainsString('label_de', $csv1);

    // Add German.
    ConfigurableLanguage::createFromLangcode('de')->save();
    $this->container->get('language_manager')->reset();

    // Export again - should now include de.
    $csv2 = $manager->export('csv');
    $this->assertStringContainsString('label_en', $csv2);
    $this->assertStringContainsString('label_fr', $csv2);
    $this->assertStringContainsString('label_de', $csv2);
  }

  /**
   * Tests import only stores active language translations.
   */
  public function testImportOnlyStoresActiveLanguages(): void {
    // Site has only en and fr.
    ConfigurableLanguage::createFromLangcode('fr')->save();

    // Import CSV with 4 languages.
    $csv = "id,label_en,label_fr,label_de,label_es,description_en,description_fr,description_de,description_es,value_type,group,weight,is_required,unit,dictionary_type,validation_min,validation_max,icon\n";
    $csv .= "filter_test,EN,FR,DE,ES,Desc EN,Desc FR,Desc DE,Desc ES,numeric,test,0,FALSE,m,,,0,100,";

    $manager = $this->container->get('ps_features.import_export');
    $report = $manager->import($csv, 'csv');

    $this->assertTrue($report['success']);

    // Load and check only en, fr stored.
    $storage = $this->container->get('entity_type.manager')->getStorage('ps_feature');
    $feature = $storage->load('filter_test');

    $metadata = $feature->getMetadata();
    $translations = $metadata['translations'] ?? [];

    $this->assertCount(2, $translations);
    $this->assertArrayHasKey('en', $translations);
    $this->assertArrayHasKey('fr', $translations);
    $this->assertArrayNotHasKey('de', $translations);
    $this->assertArrayNotHasKey('es', $translations);
  }

}
