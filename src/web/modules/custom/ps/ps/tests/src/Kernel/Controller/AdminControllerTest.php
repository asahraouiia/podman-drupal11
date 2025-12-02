<?php

declare(strict_types=1);

namespace Drupal\Tests\ps\Kernel\Controller;

use Drupal\KernelTests\KernelTestBase;
use Drupal\ps\Controller\AdminController;

/**
 * Tests AdminController.
 *
 * @coversDefaultClass \Drupal\ps\Controller\AdminController
 * @group ps
 */
class AdminControllerTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['ps', 'system', 'user'];

  /**
   * The admin controller.
   *
   * @var \Drupal\ps\Controller\AdminController
   */
  protected AdminController $controller;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->installConfig(['ps']);
    $this->controller = AdminController::create($this->container);
  }

  /**
   * Tests overview method returns render array.
   *
   * @covers ::overview
   */
  public function testOverview(): void {
    $build = $this->controller->overview();
    $this->assertIsArray($build);
    $this->assertArrayHasKey('#type', $build);
    $this->assertSame('container', $build['#type']);
    $this->assertArrayHasKey('system_info', $build);
    $this->assertArrayHasKey('widgets', $build);
  }

  /**
   * Tests overview displays system information.
   *
   * @covers ::overview
   */
  public function testOverviewSystemInfo(): void {
    $build = $this->controller->overview();
    $this->assertArrayHasKey('system_info', $build);
    $this->assertArrayHasKey('items', $build['system_info']);
    $this->assertArrayHasKey('#items', $build['system_info']['items']);
    $this->assertIsArray($build['system_info']['items']['#items']);
  }

  /**
   * Tests overview handles no widgets gracefully.
   *
   * @covers ::overview
   */
  public function testOverviewNoWidgets(): void {
    $build = $this->controller->overview();
    $this->assertArrayHasKey('widgets', $build);
    // With no additional modules, should show empty message.
    $this->assertArrayHasKey('empty', $build['widgets']);
  }

}
