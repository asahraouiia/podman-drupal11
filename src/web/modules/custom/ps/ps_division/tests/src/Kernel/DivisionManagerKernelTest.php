<?php

declare(strict_types=1);

namespace Drupal\Tests\ps_division\Kernel;

use Drupal\KernelTests\KernelTestBase;

/**
 * Kernel tests for DivisionManager high-level operations.
 *
 * @group ps_division
 */
final class DivisionManagerKernelTest extends KernelTestBase {

  protected function setUp(): void {
    parent::setUp();
    $this->installEntitySchema('ps_division');
  }

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'system',
    'user',
    'field',
    'options',
    'text',
    'ps',
    'ps_dictionary',
    'ps_division',
  ];

  /**
   * Tests getByParent and calculateTotalSurface.
   */
  public function testParentRetrievalAndTotal(): void {
    $storage = $this->container->get('entity_type.manager')->getStorage('ps_division');

    // Create three divisions for parent 500.
    $values = [22.0, 15.75, 8.40];
    foreach ($values as $i => $v) {
      $storage->create([
        'entity_id' => 500,
        'building_name' => 'Parent 500 - Lot ' . ($i + 1),
        'surfaces' => [ ['value' => $v, 'unit' => 'M2'], ],
      ])->save();
    }

    $manager = $this->container->get('ps_division.manager');
    $divisions = $manager->getByParent(500);
    $this->assertCount(3, $divisions);

    $total = $manager->calculateTotalSurface(500);
    $this->assertSame(46.15, $total);
  }

  /**
   * Tests validation logic for negative and invalid codes.
   */
  public function testValidationLogic(): void {
    $storage = $this->container->get('entity_type.manager')->getStorage('ps_division');

    $division = $storage->create([
      'entity_id' => 700,
      'building_name' => 'Validation Building',
      'surfaces' => [
        ['value' => -5, 'unit' => 'M2'],
        ['value' => 10, 'unit' => 'M2', 'type' => 'INVALID_CODE'],
      ],
    ]);

    $manager = $this->container->get('ps_division.manager');
    $errors = $manager->validate($division);

    // Expect error for negative value (message defined in DivisionManager::validate()).
    $this->assertTrue(in_array('Surface #0: value cannot be negative.', $errors, TRUE));
    // Expect error for invalid type code on second surface.
    $this->assertTrue(in_array("Surface #1: invalid type 'INVALID_CODE'.", $errors, TRUE));
  }
}
