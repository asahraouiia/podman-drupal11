<?php

declare(strict_types=1);

namespace Drupal\Tests\ps\Unit\Event;

use Drupal\ps\Event\PropertySearchEvent;
use Drupal\Tests\UnitTestCase;

/**
 * Tests PropertySearchEvent base class.
 *
 * @coversDefaultClass \Drupal\ps\Event\PropertySearchEvent
 * @group ps
 */
class PropertySearchEventTest extends UnitTestCase {

  /**
   * Tests getContext method.
   *
   * @covers ::getContext
   */
  public function testGetContext(): void {
    $event = new class () extends PropertySearchEvent {

      /**
       * {@inheritdoc}
       */
      public function getContext(): array {
        return ['test' => 'value'];
      }

    };

    $context = $event->getContext();
    $this->assertIsArray($context);
    $this->assertSame(['test' => 'value'], $context);
  }

  /**
   * Tests default getContext returns empty array.
   *
   * @covers ::getContext
   */
  public function testGetContextDefault(): void {
    $event = new class () extends PropertySearchEvent {
    };

    $context = $event->getContext();
    $this->assertIsArray($context);
    $this->assertEmpty($context);
  }

}
