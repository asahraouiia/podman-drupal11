<?php

declare(strict_types=1);

namespace Drupal\Tests\ps_features\Kernel\Service;

use Drupal\KernelTests\KernelTestBase;
use Drupal\ps_features\Entity\Feature;
use Drupal\ps_features\Service\FeatureManagerInterface;

/**
 * Kernel tests for FeatureManager service.
 *
 * @group ps_features
 * @coversDefaultClass \Drupal\ps_features\Service\FeatureManager
 */
final class FeatureManagerKernelTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'user',
    'system',
    'ps',
    'ps_dictionary',
    'ps_features',
  ];

  /**
   * The feature manager service.
   */
  private FeatureManagerInterface $featureManager;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->installConfig(['ps_features']);
    $this->featureManager = $this->container->get('ps_features.manager');
  }

  /**
   * Tests loading a feature from configuration.
   *
   * @covers ::getFeature
   */
  public function testGetFeatureFromConfig(): void {
    $feature = $this->featureManager->getFeature('has_elevator');
    $this->assertNotNull($feature);
    $this->assertInstanceOf(Feature::class, $feature);
    $this->assertSame('has_elevator', $feature->id());
    $this->assertSame('flag', $feature->getValueType());
  }

  /**
   * Tests getting all features.
   *
   * @covers ::getFeatures
   */
  public function testGetAllFeatures(): void {
    $features = $this->featureManager->getFeatures();
    $this->assertNotEmpty($features);
    $this->assertGreaterThanOrEqual(10, count($features));
  }

  /**
   * Tests filtering features by value type.
   *
   * @covers ::getFeatures
   */
  public function testGetFeaturesByValueType(): void {
    $flagFeatures = $this->featureManager->getFeatures(['value_type' => 'flag']);
    $this->assertNotEmpty($flagFeatures);

    foreach ($flagFeatures as $feature) {
      $this->assertSame('flag', $feature->getValueType());
    }
  }

  /**
   * Tests filtering features by group.
   *
   * @covers ::getFeatures
   */
  public function testGetFeaturesByGroup(): void {
    $features = $this->featureManager->getFeatures(['group' => 'comfort']);
    $this->assertIsArray($features);

    foreach ($features as $feature) {
      $this->assertSame('comfort', $feature->getGroup());
    }
  }

  /**
   * Tests checking feature existence.
   *
   * @covers ::hasFeature
   */
  public function testHasFeature(): void {
    $this->assertTrue($this->featureManager->hasFeature('has_elevator'));
    $this->assertFalse($this->featureManager->hasFeature('non_existent_feature'));
  }

  /**
   * Tests getting features grouped by category.
   *
   * @covers ::getFeaturesByGroup
   */
  public function testGetFeaturesByGroupMethod(): void {
    $grouped = $this->featureManager->getFeaturesByGroup();
    $this->assertIsArray($grouped);
    $this->assertNotEmpty($grouped);

    foreach ($grouped as $group => $features) {
      $this->assertIsString($group);
      $this->assertIsArray($features);
      $this->assertNotEmpty($features);
    }
  }

  /**
   * Tests getting value types.
   *
   * @covers ::getValueTypes
   */
  public function testGetValueTypes(): void {
    $valueTypes = $this->featureManager->getValueTypes();
    $this->assertIsArray($valueTypes);
    $this->assertArrayHasKey('flag', $valueTypes);
    $this->assertArrayHasKey('yesno', $valueTypes);
    $this->assertArrayHasKey('numeric', $valueTypes);
    $this->assertArrayHasKey('range', $valueTypes);
    $this->assertArrayHasKey('dictionary', $valueTypes);
    $this->assertArrayHasKey('string', $valueTypes);
  }

  /**
   * Tests cache invalidation.
   *
   * @covers ::clearCache
   */
  public function testClearCache(): void {
    // Load features to populate cache.
    $features1 = $this->featureManager->getFeatures();
    $this->assertNotEmpty($features1);

    // Clear cache.
    $this->featureManager->clearCache();

    // Load again (should rebuild cache).
    $features2 = $this->featureManager->getFeatures();
    $this->assertNotEmpty($features2);
    $this->assertCount(count($features1), $features2);
  }

  /**
   * Tests creating a new feature programmatically.
   */
  public function testCreateFeatureProgrammatically(): void {
    $feature = Feature::create([
      'id' => 'test_feature',
      'label' => 'Test Feature',
      'value_type' => 'numeric',
      'unit' => 'm²',
      'is_required' => FALSE,
      'validation_rules' => ['min' => 0, 'max' => 1000],
      'group' => 'test',
      'weight' => 100,
    ]);
    $feature->save();

    // Clear cache to reload.
    $this->featureManager->clearCache();

    $loaded = $this->featureManager->getFeature('test_feature');
    $this->assertNotNull($loaded);
    $this->assertSame('Test Feature', $loaded->label());
    $this->assertSame('numeric', $loaded->getValueType());
    $this->assertSame('m²', $loaded->getUnit());

    // Clean up.
    $feature->delete();
  }

}
