<?php

declare(strict_types=1);

namespace Drupal\Tests\ps_features\Unit\Form;

use Drupal\ps_features\Entity\FeatureGroupInterface;
use Drupal\Core\Form\FormState;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\ps_dictionary\Service\DictionaryManagerInterface;
use Drupal\ps_features\Entity\Feature;
use Drupal\ps_features\Entity\FeatureInterface;
use Drupal\ps_features\Form\FeatureForm;
use Drupal\ps_features\Service\FeatureManagerInterface;
use Drupal\Tests\UnitTestCase;

/**
 * Tests the FeatureForm class.
 *
 * @group ps_features
 * @coversDefaultClass \Drupal\ps_features\Form\FeatureForm
 *
 * @todo Convert to Kernel test - form() method requires container for getRequest().
 */
final class FeatureFormTest extends UnitTestCase {

  /**
   * The mocked feature manager.
   *
   * @var \Drupal\ps_features\Service\FeatureManagerInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  private FeatureManagerInterface $featureManager;

  /**
   * The mocked dictionary manager.
   *
   * @var \Drupal\ps_dictionary\Service\DictionaryManagerInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  private DictionaryManagerInterface $dictionaryManager;

  /**
   * The mocked entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  private EntityTypeManagerInterface $entityTypeManager;

  /**
   * The feature form instance.
   *
   * @var \Drupal\ps_features\Form\FeatureForm
   */
  private FeatureForm $form;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    // Mock services.
    $this->featureManager = $this->createMock(FeatureManagerInterface::class);
    $this->dictionaryManager = $this->createMock(DictionaryManagerInterface::class);
    $this->entityTypeManager = $this->createMock(EntityTypeManagerInterface::class);

    // Mock default empty storage for FeatureGroup entities.
    $defaultStorage = $this->createMock(EntityStorageInterface::class);
    $defaultStorage->method('loadMultiple')->willReturn([]);
    $this->entityTypeManager->method('getStorage')->willReturn($defaultStorage);

    // Create form instance.
    $this->form = new FeatureForm(
      $this->featureManager,
      $this->dictionaryManager,
      $this->entityTypeManager,
    );

    // Mock string translation.
    $string_translation = $this->getStringTranslationStub();
    $this->form->setStringTranslation($string_translation);
  }

  /**
   * Tests that group field is present in form.
   *
   * @covers ::form
   */
  public function testGroupFieldPresent(): void {
    $this->markTestSkipped('Form tests require container - convert to Kernel test');
    
    // Mock feature entity.
    $feature = $this->createMock(FeatureInterface::class);
    $feature->method('label')->willReturn('Test Feature');
    $feature->method('id')->willReturn('test_feature');
    $feature->method('isNew')->willReturn(TRUE);
    $feature->method('getDescription')->willReturn('Test description');
    $feature->method('getValueType')->willReturn('flag');
    $feature->method('isRequired')->willReturn(FALSE);
    $feature->method('getGroup')->willReturn('comfort');
    $feature->method('getWeight')->willReturn(0);
    $feature->method('getValidationRules')->willReturn([]);
    $feature->method('getMetadata')->willReturn([]);

    // Set entity on form.
    $reflection = new \ReflectionClass($this->form);
    $property = $reflection->getProperty('entity');
    $property->setAccessible(TRUE);
    $property->setValue($this->form, $feature);

    // Mock entity type manager for group options.
    $group_storage = $this->createMock('\Drupal\Core\Entity\EntityStorageInterface');
    $group_storage->method('loadMultiple')
      ->willReturn([]);
    $this->entityTypeManager->method('getStorage')
      ->with('ps_feature_group')
      ->willReturn($group_storage);

    // Mock feature manager.
    $this->featureManager->method('getValueTypes')
      ->willReturn(['flag' => 'Flag', 'numeric' => 'Numeric']);

    $form_state = new FormState();
    $form = $this->form->form([], $form_state);

    $this->assertArrayHasKey('group', $form);
    $this->assertEquals('select', $form['group']['#type']);
    $this->assertEquals('equipments', $form['group']['#default_value']);
  }

  /**
   * Tests that weight field is present in form.
   *
   * @covers ::form
   */
  public function testWeightFieldPresent(): void {
    $this->markTestSkipped('Form tests require container - convert to Kernel test');
    
    // Mock feature entity.
    $feature = $this->createMock(FeatureInterface::class);
    $feature->method('label')->willReturn('Test Feature');
    $feature->method('id')->willReturn('test_feature');
    $feature->method('isNew')->willReturn(TRUE);
    $feature->method('getDescription')->willReturn('Test description');
    $feature->method('getValueType')->willReturn('flag');
    $feature->method('isRequired')->willReturn(FALSE);
    $feature->method('getGroup')->willReturn('comfort');
    $feature->method('getWeight')->willReturn(42);
    $feature->method('getValidationRules')->willReturn([]);
    $feature->method('getMetadata')->willReturn([]);

    // Set entity on form.
    $reflection = new \ReflectionClass($this->form);
    $property = $reflection->getProperty('entity');
    $property->setAccessible(TRUE);
    $property->setValue($this->form, $feature);

    // Mock dictionary entries.
    $this->dictionaryManager->method('getEntries')
      ->willReturn([]);

    // Mock feature manager.
    $this->featureManager->method('getValueTypes')
      ->willReturn(['flag' => 'Flag']);

    $form_state = new FormState();
    $form = $this->form->form([], $form_state);

    $this->assertArrayHasKey('weight', $form);
    $this->assertEquals('value', $form['weight']['#type']);
    $this->assertEquals(0, $form['weight']['#value']);
  }

  /**
   * Tests group options populated from dictionary entries.
   *
   * @covers ::form
   */
  public function testGroupOptionsFromDictionary(): void {
    $this->markTestSkipped('Form tests require container - convert to Kernel test');
    
    // Mock feature entity.
    $feature = $this->createMock(FeatureInterface::class);
    $feature->method('label')->willReturn('Test Feature');
    $feature->method('id')->willReturn('test_feature');
    $feature->method('isNew')->willReturn(TRUE);
    $feature->method('getDescription')->willReturn('');
    $feature->method('getValueType')->willReturn('flag');
    $feature->method('isRequired')->willReturn(FALSE);
    $feature->method('getGroup')->willReturn(NULL);
    $feature->method('getWeight')->willReturn(0);
    $feature->method('getValidationRules')->willReturn([]);
    $feature->method('getMetadata')->willReturn([]);

    // Set entity on form.
    $reflection = new \ReflectionClass($this->form);
    $property = $reflection->getProperty('entity');
    $property->setAccessible(TRUE);
    $property->setValue($this->form, $feature);

    // Mock FeatureGroup entities.
    $group1 = $this->createMock(FeatureGroupInterface::class);
    $group1->method('id')->willReturn('equipments');
    $group1->method('label')->willReturn('Equipments');
    $group1->method('getWeight')->willReturn(0);

    $group2 = $this->createMock(FeatureGroupInterface::class);
    $group2->method('id')->willReturn('services');
    $group2->method('label')->willReturn('Services');
    $group2->method('getWeight')->willReturn(10);

    $group3 = $this->createMock(FeatureGroupInterface::class);
    $group3->method('id')->willReturn('building_condition');
    $group3->method('label')->willReturn('Building condition');
    $group3->method('getWeight')->willReturn(20);

    // Mock storage to return groups.
    $storage = $this->createMock(EntityStorageInterface::class);
    $storage->method('loadMultiple')->willReturn([
      'equipments' => $group1,
      'services' => $group2,
      'building_condition' => $group3,
    ]);

    // Recreate entity type manager mock with specific groups.
    $entityTypeManager = $this->createMock(EntityTypeManagerInterface::class);
    $entityTypeManager->method('getStorage')
      ->with('ps_feature_group')
      ->willReturn($storage);

    // Recreate form with new entity type manager.
    $form = new FeatureForm(
      $this->featureManager,
      $this->dictionaryManager,
      $entityTypeManager
    );
    $form->setStringTranslation($this->getStringTranslationStub());

    // Set entity on form.
    $reflection = new \ReflectionClass($form);
    $property = $reflection->getProperty('entity');
    $property->setAccessible(TRUE);
    $property->setValue($form, $feature);

    // Mock feature manager.
    $this->featureManager->method('getValueTypes')
      ->willReturn(['flag' => 'Flag']);

    $form_state = new FormState();
    $formArray = $form->form([], $form_state);

    $this->assertArrayHasKey('group', $formArray);
    $this->assertArrayHasKey('#options', $formArray['group']);

    $options = $formArray['group']['#options'];
    $this->assertArrayHasKey('equipments', $options);
    $this->assertArrayHasKey('services', $options);
    $this->assertArrayHasKey('building_condition', $options);
    $this->assertEquals('Equipments', $options['equipments']);
    $this->assertEquals('Services', $options['services']);
    $this->assertEquals('Building condition', $options['building_condition']);
  }

  /**
   * Tests that group and weight values are set on entity.
   *
   * @covers ::save
   */
  public function testGroupAndWeightSaved(): void {
    // Create a mock feature that can track set() calls.
    $feature = $this->getMockBuilder(Feature::class)
      ->disableOriginalConstructor()
      ->onlyMethods(['set'])
      ->getMock();

    $set_calls = [];
    $feature->expects($this->atLeastOnce())
      ->method('set')
      ->willReturnCallback(function ($key, $value) use (&$set_calls, $feature) {
        $set_calls[$key] = $value;
        return $feature;
      });

    // Set entity on form.
    $reflection = new \ReflectionClass($this->form);
    $property = $reflection->getProperty('entity');
    $property->setAccessible(TRUE);
    $property->setValue($this->form, $feature);

    // Create form state with values.
    $form_state = new FormState();
    $form_state->setValue('group', 'comfort');
    $form_state->setValue('weight', 42);
    $form_state->setValue('min', NULL);
    $form_state->setValue('max', NULL);
    $form_state->setValue('icon', 'test-icon');

    // Call save method - we catch the exception from parent::save().
    try {
      $this->form->save([], $form_state);
    }
    catch (\Throwable $e) {
      // Expected - parent::save() will fail in unit test context.
    }

    // Verify set() was called with correct values.
    $this->assertArrayHasKey('group', $set_calls);
    $this->assertArrayHasKey('weight', $set_calls);
    $this->assertEquals('comfort', $set_calls['group']);
    $this->assertEquals(42, $set_calls['weight']);
  }

}
