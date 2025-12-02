<?php

declare(strict_types=1);

namespace Drupal\ps_price\Event;

use Drupal\Core\Entity\EntityInterface;
use Symfony\Contracts\EventDispatcher\Event;

/**
 * Event fired when a parent entity containing a ps_price field is changed.
 *
 * This event allows other modules to react to entity changes and potentially
 * update price field values, apply rules, or trigger recalculations.
 *
 * Example use cases:
 * - Auto-apply price rules based on property type
 * - Update unit/period codes based on transaction type
 * - Recalculate derived prices
 *
 * @see docs/modules/ps_price.md#events
 */
final class PriceFieldParentEntityEvent extends Event {

  /**
   * Event name constant.
   */
  public const EVENT_NAME = 'ps_price.parent_entity_changed';

  /**
   * Constructs a PriceFieldParentEntityEvent object.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The parent entity containing ps_price field(s).
   * @param string $fieldName
   *   The name of the price field being affected.
   * @param int $delta
   *   The delta (index) of the field value.
   * @param string $operation
   *   The operation: 'presave', 'insert', 'update', 'delete'.
   */
  public function __construct(
    private readonly EntityInterface $entity,
    private readonly string $fieldName,
    private readonly int $delta,
    private readonly string $operation,
  ) {}

  /**
   * Gets the parent entity.
   *
   * @return \Drupal\Core\Entity\EntityInterface
   *   The entity containing the price field.
   */
  public function getEntity(): EntityInterface {
    return $this->entity;
  }

  /**
   * Gets the field name.
   *
   * @return string
   *   The price field name.
   */
  public function getFieldName(): string {
    return $this->fieldName;
  }

  /**
   * Gets the field delta.
   *
   * @return int
   *   The field value index.
   */
  public function getDelta(): int {
    return $this->delta;
  }

  /**
   * Gets the operation type.
   *
   * @return string
   *   One of: 'presave', 'insert', 'update', 'delete'.
   */
  public function getOperation(): string {
    return $this->operation;
  }

}
