<?php

declare(strict_types=1);

namespace Drupal\Tests\ps_features\Kernel;

use Drupal\KernelTests\KernelTestBase;
use Drupal\language\Entity\ConfigurableLanguage;

/**
 * Tests the ps:features:validate Drush command.
 *
 * @group ps_features
 */
class DrushCommandTest extends KernelTestBase {

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
   * The import/export manager.
   *
   * @var \Drupal\ps_features\Service\FeatureImportExportManagerInterface
   */
  private $importExportManager;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->installEntitySchema('user');
    $this->installConfig(['ps', 'ps_dictionary', 'ps_features']);

    ConfigurableLanguage::createFromLangcode('fr')->save();
    ConfigurableLanguage::createFromLangcode('de')->save();

    $this->importExportManager = $this->container->get('ps_features.import_export');
  }

  /**
   * Tests validation with complete translations.
   */
  public function testValidateCompleteTranslations(): void {
    $csv = "id,label_en,label_fr,label_de,description_en,description_fr,description_de,value_type,group,weight,is_required,unit,dictionary_type,validation_min,validation_max,icon\n";
    $csv .= "complete,English,Français,Deutsch,Desc EN,Desc FR,Desc DE,flag,test,0,FALSE,,,,,";

    $validation = $this->importExportManager->validate($csv, 'csv', [
      'check_translations' => TRUE,
    ]);

    $this->assertTrue($validation['valid']);
    $this->assertEmpty($validation['errors']);
  }

  /**
   * Tests validation with missing translations.
   */
  public function testValidateMissingTranslations(): void {
    $csv = "id,label_en,label_fr,description_en,description_fr,description_de,value_type,group,weight,is_required,unit,dictionary_type,validation_min,validation_max,icon\n";
    $csv .= "incomplete,English,Français,Desc EN,Desc FR,,flag,test,0,FALSE,,,,,";

    $validation = $this->importExportManager->validate($csv, 'csv', [
      'check_translations' => TRUE,
      'strict' => TRUE,
    ]);

    $this->assertFalse($validation['valid']);
    $this->assertNotEmpty($validation['errors']);
  }

  /**
   * Tests validation with missing language columns.
   */
  public function testValidateMissingColumns(): void {
    // Missing label_de and description_de columns.
    $csv = "id,label_en,label_fr,description_en,description_fr,value_type,group,weight,is_required,unit,dictionary_type,validation_min,validation_max,icon\n";
    $csv .= "missing_cols,English,Français,Desc EN,Desc FR,flag,test,0,FALSE,,,,,";

    $validation = $this->importExportManager->validate($csv, 'csv', [
      'check_translations' => TRUE,
    ]);

    $this->assertFalse($validation['valid']);
    $this->assertNotEmpty($validation['errors']);
    // Should mention missing de columns.
    $errors = implode(' ', $validation['errors']);
    $this->assertStringContainsString('de', $errors);
  }

}
