<?php

declare(strict_types=1);

namespace Drupal\Tests\ps_division\Kernel;

use Drupal\KernelTests\KernelTestBase;
use Drupal\ps_division\Entity\DivisionInterface;

/**
 * Kernel tests for ps_division entity & services.
 *
 * @group ps_division
 */
final class DivisionKernelTest extends KernelTestBase {

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
   * Tests entity creation and surface aggregation.
   */
  public function testDivisionCreationAndAggregation(): void {
    /** @var \Drupal\ps_division\Entity\DivisionInterface $division */
    $division = $this->container->get('entity_type.manager')->getStorage('ps_division')->create([
      'entity_id' => 99,
      'building_name' => 'Kernel Tower - A1',
      'floor' => '1',
      'type' => 'APPT',
      'nature' => 'HAB',
      'lot' => 'A1',
      'surfaces' => [
        ['value' => 50.00, 'unit' => 'M2', 'type' => 'living', 'nature' => 'habitable', 'qualification' => 'carrez'],
        ['value' => 12.50, 'unit' => 'M2', 'type' => 'balcony', 'nature' => 'exterior'],
      ],
    ]);
    $division->save();

    $this->assertInstanceOf(DivisionInterface::class, $division);
    $this->assertSame(62.50, $division->getTotalSurface());
  }

  /**
   * Tests aggregates service cache invalidation.
   */
  public function testAggregatesServiceCacheInvalidation(): void {
    $storage = $this->container->get('entity_type.manager')->getStorage('ps_division');

    // First division.
    $d1 = $storage->create([
      'entity_id' => 100,
      'building_name' => 'Block B - Lot 1',
      'surfaces' => [ ['value' => 40, 'unit' => 'M2'], ],
    ]);
    $d1->save();

    $aggregates = $this->container->get('ps_division.aggregates');
    $total1 = $aggregates->getTotalSurface(100);
    $this->assertSame(40.0, $total1);

    // Second division should invalidate parent cache via hook.
    $d2 = $storage->create([
      'entity_id' => 100,
      'building_name' => 'Block B - Lot 2',
      'surfaces' => [ ['value' => 35.25, 'unit' => 'M2'], ],
    ]);
    $d2->save();

    $total2 = $aggregates->getTotalSurface(100);
    $this->assertSame(75.25, $total2);
  }

  /**
   * Tests DivisionManager service summary structure.
   */
  public function testDivisionManagerSummary(): void {
    $storage = $this->container->get('entity_type.manager')->getStorage('ps_division');
    $division = $storage->create([
      'entity_id' => 200,
      'building_name' => 'Summary Building',
      'type' => 'APPT',
      'nature' => 'HAB',
      'lot' => 'S-200',
      'surfaces' => [ ['value' => 28.3, 'unit' => 'M2'], ],
    ]);
    $division->save();

    $manager = $this->container->get('ps_division.manager');
    $summary = $manager->getSummary($division);

    $this->assertSame('Summary Building', $summary['building_name']);
    $this->assertSame('S-200', $summary['lot']);
    $this->assertSame(28.3, $summary['total_surface']);
  }
}
