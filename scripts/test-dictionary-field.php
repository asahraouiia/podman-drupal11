<?php

/**
 * @file
 * Test script for PropertySearch dictionary field integration.
 */

use Drupal\node\Entity\NodeType;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\field\Entity\FieldConfig;
use Drupal\Core\Entity\Entity\EntityFormDisplay;
use Drupal\Core\Entity\Entity\EntityViewDisplay;
use Drupal\node\Entity\Node;

// Step 1: Create Offer content type.
echo "=== Step 1: Creating Offer content type ===\n";
$node_type_storage = \Drupal::entityTypeManager()->getStorage('node_type');
if (!$node_type_storage->load('offer')) {
  $offer_type = $node_type_storage->create([
    'type' => 'offer',
    'name' => 'Offer',
    'description' => 'Real estate offer content type for PropertySearch',
  ]);
  $offer_type->save();
  echo "✓ Content type 'Offer' created\n";
} else {
  echo "✓ Content type 'Offer' already exists\n";
}

// Step 2: List available dictionary types.
echo "\n=== Step 2: Listing dictionary types ===\n";
$dict_manager = \Drupal::service('ps_dictionary.manager');
$dict_type_storage = \Drupal::entityTypeManager()->getStorage('ps_dictionary_type');
$dict_types = $dict_type_storage->loadMultiple();
echo "Available dictionary types:\n";
foreach ($dict_types as $type_id => $type) {
  $entries = $dict_manager->getEntries($type_id);
  echo "  - {$type_id}: {$type->label()} (" . count($entries) . " entries)\n";
}

// Check if customer_type exists.
$customer_type_exists = isset($dict_types['customer_type']);
echo "\ncustomer_type exists: " . ($customer_type_exists ? 'YES' : 'NO') . "\n";

if (!$customer_type_exists) {
  echo "⚠ customer_type not found. Available types: " . implode(', ', array_keys($dict_types)) . "\n";
  echo "Using 'property_type' as fallback for testing.\n";
  $test_dict_type = 'property_type';
} else {
  $test_dict_type = 'customer_type';
}

// Step 3: Create field storage and field instance.
echo "\n=== Step 3: Creating dictionary field ===\n";
$field_name = 'field_customer_type';

// Create field storage if not exists.
$field_storage = FieldStorageConfig::loadByName('node', $field_name);
if (!$field_storage) {
  $field_storage = FieldStorageConfig::create([
    'field_name' => $field_name,
    'entity_type' => 'node',
    'type' => 'ps_dictionary',
    'settings' => [
      'dictionary_type' => $test_dict_type,
    ],
    'cardinality' => 1,
  ]);
  $field_storage->save();
  echo "✓ Field storage '{$field_name}' created with dictionary_type={$test_dict_type}\n";
} else {
  echo "✓ Field storage '{$field_name}' already exists\n";
}

// Create field instance if not exists.
$field = FieldConfig::loadByName('node', 'offer', $field_name);
if (!$field) {
  $field = FieldConfig::create([
    'field_name' => $field_name,
    'entity_type' => 'node',
    'bundle' => 'offer',
    'label' => 'Customer Type',
    'required' => FALSE,
  ]);
  $field->save();
  echo "✓ Field instance '{$field_name}' added to 'offer' bundle\n";
} else {
  echo "✓ Field instance '{$field_name}' already exists on 'offer'\n";
}

// Step 4: Configure form display and view display.
echo "\n=== Step 4: Configuring displays ===\n";

// Form display.
$form_display = EntityFormDisplay::load('node.offer.default');
if (!$form_display) {
  $form_display = EntityFormDisplay::create([
    'targetEntityType' => 'node',
    'bundle' => 'offer',
    'mode' => 'default',
    'status' => TRUE,
  ]);
}
$form_display->setComponent($field_name, [
  'type' => 'ps_dictionary_select',
  'weight' => 10,
  'settings' => [],
]);
$form_display->save();
echo "✓ Form display configured (widget: ps_dictionary_select)\n";

// View display.
$view_display = EntityViewDisplay::load('node.offer.default');
if (!$view_display) {
  $view_display = EntityViewDisplay::create([
    'targetEntityType' => 'node',
    'bundle' => 'offer',
    'mode' => 'default',
    'status' => TRUE,
  ]);
}
$view_display->setComponent($field_name, [
  'type' => 'ps_dictionary_label',
  'weight' => 10,
  'label' => 'above',
  'settings' => [
    'link_to_entity' => FALSE,
  ],
]);
$view_display->save();
echo "✓ View display configured (formatter: ps_dictionary_label)\n";

// Step 5: Get available options and create test content.
echo "\n=== Step 5: Creating test content ===\n";

// Get available options for the dictionary type.
$options = $dict_manager->getOptions($test_dict_type);
echo "Available options for '{$test_dict_type}':\n";
foreach ($options as $code => $label) {
  echo "  - {$code}: {$label}\n";
}

// Pick the first available option.
$test_code = array_key_first($options);
echo "\nUsing test value: {$test_code} ({$options[$test_code]})\n";

// Create a test node.
$node = Node::create([
  'type' => 'offer',
  'title' => 'Test Offer - Dictionary Field Validation',
  $field_name => $test_code,
  'status' => 1,
]);
$node->save();
$node_id = $node->id();
echo "✓ Test node created with ID: {$node_id}\n";

// Step 6: Validate the contributed value.
echo "\n=== Step 6: Validating contributed value ===\n";

// Reload the node.
$loaded_node = Node::load($node_id);
$field_value = $loaded_node->get($field_name)->value;
$field_label = $loaded_node->get($field_name)->first() ? 
  $dict_manager->getLabel($test_dict_type, $field_value) : NULL;

echo "Node ID: {$node_id}\n";
echo "Node Title: {$loaded_node->getTitle()}\n";
echo "Field Name: {$field_name}\n";
echo "Field Value (code): {$field_value}\n";
echo "Field Label: {$field_label}\n";

// Validate.
$is_valid = $dict_manager->isValid($test_dict_type, $field_value);
echo "\nValidation Result: " . ($is_valid ? '✓ VALID' : '✗ INVALID') . "\n";

if ($field_value === $test_code && $is_valid) {
  echo "\n✅ TEST PASSED: Dictionary field integration works correctly!\n";
  echo "   - Content type created: Offer\n";
  echo "   - Dictionary field added: {$field_name}\n";
  echo "   - Dictionary type: {$test_dict_type}\n";
  echo "   - Test value saved: {$test_code}\n";
  echo "   - Test value validated: {$field_label}\n";
} else {
  echo "\n❌ TEST FAILED: Value mismatch or invalid\n";
  echo "   Expected: {$test_code}\n";
  echo "   Got: {$field_value}\n";
}

echo "\n=== Test Complete ===\n";
