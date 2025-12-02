<?php

declare(strict_types=1);

/**
 * @file
 * Configuration schema tests for ps_features module.
 */

namespace Drupal\Tests\ps_features\Kernel;

use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\KernelTests\KernelTestBase;
use Drupal\node\Entity\NodeType;

/**
 * Tests configuration schema for ps_feature_value field type.
 *
 * Verifies that field storage and field instance configurations have valid
 * schema definitions when ps_feature_value fields are created.
 *
 * @group ps_features
 * @group ps_features_kernel
 */
final class ConfigSchemaTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'field',
    'node',
    'system',
    'user',
    'ps',
    'ps_dictionary',
    'ps_features',
  ];

  /**
   * The typed config manager service.
   *
   * @var \Drupal\Core\Config\TypedConfigManagerInterface
   */
  private $typedConfigManager;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->installEntitySchema('node');
    $this->installEntitySchema('user');
    $this->installConfig(['ps_dictionary', 'ps_features']);

    $this->typedConfigManager = $this->container->get('config.typed');

    // Create a test content type.
    NodeType::create([
      'type' => 'test_content',
      'name' => 'Test Content',
    ])->save();
  }

  /**
   * Tests that field storage configuration has a valid schema.
   *
   * This test verifies that when a ps_feature_value field storage is created,
   * the configuration has a properly defined schema and is not "Undefined".
   *
   * @see https://www.drupal.org/docs/drupal-apis/configuration-api/configuration-schemametadata
   */
  public function testFieldStorageConfigSchema(): void {
    // Create field storage for ps_feature_value.
    $field_storage = FieldStorageConfig::create([
      'field_name' => 'field_test_features',
      'entity_type' => 'node',
      'type' => 'ps_feature_value',
      'cardinality' => -1,
    ]);
    $field_storage->save();

    $config_name = 'field.storage.node.field_test_features';

    // Get the schema definition for the field storage config.
    $definition = $this->typedConfigManager->getDefinition($config_name);

    // Check if schema is defined (not Undefined class).
    $this->assertNotEquals(
      'Undefined',
      $definition['label'] ?? '',
      sprintf(
        'Field storage configuration "%s" should have a defined schema. ' .
        'Got class: %s. This usually means field.storage_settings.%s is not properly defined in config/schema/field.schema.yml',
        $config_name,
        $definition['class'] ?? 'unknown',
        'ps_feature_value'
      )
    );

    $this->assertNotEquals(
      'undefined',
      $definition['type'] ?? '',
      sprintf(
        'Field storage configuration "%s" should not have type "undefined". ' .
        'Current definition: %s',
        $config_name,
        print_r($definition, TRUE)
      )
    );

    // Verify the schema can be loaded without errors.
    $config = $this->config($config_name);
    $this->assertNotNull($config, 'Field storage configuration should be loadable');

    $typed_config = $this->typedConfigManager->createFromNameAndData(
      $config_name,
      $config->getRawData()
    );

    $violations = $typed_config->validate();
    $this->assertCount(
      0,
      $violations,
      sprintf(
        'Field storage configuration "%s" should not have validation errors. Violations: %s',
        $config_name,
        (string) $violations
      )
    );
  }

  /**
   * Tests that field instance configuration has a valid schema.
   *
   * This test verifies that when a ps_feature_value field instance is created
   * on a node bundle, the configuration has a properly defined schema.
   *
   * @see https://www.drupal.org/docs/drupal-apis/configuration-api/configuration-schemametadata
   */
  public function testFieldInstanceConfigSchema(): void {
    // Create field storage first.
    FieldStorageConfig::create([
      'field_name' => 'field_test_features',
      'entity_type' => 'node',
      'type' => 'ps_feature_value',
      'cardinality' => -1,
    ])->save();

    // Create field instance.
    $field = FieldConfig::create([
      'field_name' => 'field_test_features',
      'entity_type' => 'node',
      'bundle' => 'test_content',
      'label' => 'Test Features',
    ]);
    $field->save();

    $config_name = 'field.field.node.test_content.field_test_features';

    // Get the schema definition for the field instance config.
    $definition = $this->typedConfigManager->getDefinition($config_name);

    // Check if schema is defined (not Undefined class).
    $this->assertNotEquals(
      'Undefined',
      $definition['label'] ?? '',
      sprintf(
        'Field instance configuration "%s" should have a defined schema. ' .
        'Got class: %s. This usually means field.field_settings.%s is not properly defined in config/schema/field.schema.yml',
        $config_name,
        $definition['class'] ?? 'unknown',
        'ps_feature_value'
      )
    );

    $this->assertNotEquals(
      'undefined',
      $definition['type'] ?? '',
      sprintf(
        'Field instance configuration "%s" should not have type "undefined". ' .
        'Current definition: %s',
        $config_name,
        print_r($definition, TRUE)
      )
    );

    // Verify the schema can be loaded without errors.
    $config = $this->config($config_name);
    $this->assertNotNull($config, 'Field instance configuration should be loadable');

    $typed_config = $this->typedConfigManager->createFromNameAndData(
      $config_name,
      $config->getRawData()
    );

    $violations = $typed_config->validate();
    $this->assertCount(
      0,
      $violations,
      sprintf(
        'Field instance configuration "%s" should not have validation errors. Violations: %s',
        $config_name,
        (string) $violations
      )
    );
  }

  /**
   * Tests that base schema definitions exist for ps_feature_value.
   *
   * This test verifies that the fundamental schema definitions for storage
   * settings, field settings, and field values are properly declared.
   */
  public function testBaseSchemaDefinitionsExist(): void {
    // Check field.storage_settings.ps_feature_value.
    $storage_settings_definition = $this->typedConfigManager->getDefinition('field.storage_settings.ps_feature_value');
    $this->assertNotNull(
      $storage_settings_definition,
      'Schema definition "field.storage_settings.ps_feature_value" should exist'
    );
    $this->assertNotEquals(
      'Undefined',
      $storage_settings_definition['label'] ?? '',
      'Schema definition "field.storage_settings.ps_feature_value" should not be Undefined'
    );

    // Check field.field_settings.ps_feature_value.
    $field_settings_definition = $this->typedConfigManager->getDefinition('field.field_settings.ps_feature_value');
    $this->assertNotNull(
      $field_settings_definition,
      'Schema definition "field.field_settings.ps_feature_value" should exist'
    );
    $this->assertNotEquals(
      'Undefined',
      $field_settings_definition['label'] ?? '',
      'Schema definition "field.field_settings.ps_feature_value" should not be Undefined'
    );

    // Check field.value.ps_feature_value.
    $field_value_definition = $this->typedConfigManager->getDefinition('field.value.ps_feature_value');
    $this->assertNotNull(
      $field_value_definition,
      'Schema definition "field.value.ps_feature_value" should exist'
    );
    $this->assertNotEquals(
      'Undefined',
      $field_value_definition['label'] ?? '',
      'Schema definition "field.value.ps_feature_value" should not be Undefined'
    );
  }

}
