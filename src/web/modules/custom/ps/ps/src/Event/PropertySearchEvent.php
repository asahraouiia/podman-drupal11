<?php

declare(strict_types=1);

namespace Drupal\ps\Event;

use Drupal\Component\EventDispatcher\Event;

/**
 * Base class for all PropertySearch events.
 */
abstract class PropertySearchEvent extends Event {

  /**
   * Get event context data.
   *
   * @return array<string, mixed>
   *   Context data.
   */
  public function getContext(): array {
    return [];
  }

}
