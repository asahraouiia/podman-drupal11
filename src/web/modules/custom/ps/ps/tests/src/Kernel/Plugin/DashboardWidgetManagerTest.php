<?php

declare(strict_types=1);

namespace Drupal\Tests\ps\Kernel\Plugin;

use Drupal\KernelTests\KernelTestBase;
use Drupal\ps\Plugin\DashboardWidgetManager;

/**
 * Tests DashboardWidgetManager plugin manager.
 *
 * @coversDefaultClass \Drupal\ps\Plugin\DashboardWidgetManager
 * @group ps
 */
class DashboardWidgetManagerTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['ps'];

  /**
   * The dashboard widget manager.
   *
   * @var \Drupal\ps\Plugin\DashboardWidgetManager
   */
  protected $widgetManager;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->widgetManager = $this->container->get('ps.dashboard_widget.manager');
  }

  /**
   * Tests that the manager service exists.
   *
   * @covers ::__construct
   */
  public function testServiceExists(): void {
    $this->assertInstanceOf(DashboardWidgetManager::class, $this->widgetManager);
  }

  /**
   * Tests getDefinitions method.
   */
  public function testGetDefinitions(): void {
    $definitions = $this->widgetManager->getDefinitions();
    $this->assertIsArray($definitions);
  }

}
