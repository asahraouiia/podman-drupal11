<?php

declare(strict_types=1);

namespace Drupal\ps_division\Hook;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Hook\Attribute\Hook;

/**
 * Attribute-based hooks for ps_division (regenerated minimal model).
 *
 * Uses service locator pattern per KNOWLEDGE_BASE.md guidance.
 *
 * @see docs/specs/08-ps-division.md#8-intégration-parent
 */
final class PsDivisionHooks {

  /**
   * Implements hook_entity_presave() for ps_division entities.
   */
  #[Hook('entity_presave')]
  public function entityPresave(EntityInterface $entity): void {
    if ($entity->getEntityTypeId() !== 'ps_division') {
      return;
    }

    /** @var \Drupal\ps_division\Entity\DivisionInterface $entity */
    $parent = $entity->getEntityId();
    if ($parent !== NULL) {
      // @phpstan-ignore-next-line Service locator pattern for hooks (see KNOWLEDGE_BASE.md)
      \Drupal::service('ps_division.aggregates')->invalidate($parent);
    }
  }

  /**
   * Implements hook_entity_delete() for ps_division entities.
   */
  #[Hook('entity_delete')]
  public function entityDelete(EntityInterface $entity): void {
    if ($entity->getEntityTypeId() !== 'ps_division') {
      return;
    }

    /** @var \Drupal\ps_division\Entity\DivisionInterface $entity */
    $parent = $entity->getEntityId();
    if ($parent !== NULL) {
      // @phpstan-ignore-next-line Service locator pattern for hooks (see KNOWLEDGE_BASE.md)
      \Drupal::service('ps_division.aggregates')->invalidate($parent);
    }
  }

}
