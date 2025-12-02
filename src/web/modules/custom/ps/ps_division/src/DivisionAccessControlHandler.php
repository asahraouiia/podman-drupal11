<?php

declare(strict_types=1);

namespace Drupal\ps_division;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * Minimal access control handler for Division entities.
 *
 * @see docs/specs/08-ps-division.md#6-permissions
 */
final class DivisionAccessControlHandler extends EntityAccessControlHandler {

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account): AccessResult {
    if ($operation === 'view') {
      return AccessResult::allowedIfHasPermission($account, 'view division entities')
        ->orIf(AccessResult::allowedIfHasPermission($account, 'administer ps_division entities'));
    }

    if (in_array($operation, ['update', 'delete'], TRUE)) {
      return AccessResult::allowedIfHasPermission($account, 'administer ps_division entities');
    }

    return AccessResult::neutral();
  }

  /**
   * {@inheritdoc}
   */
  protected function checkCreateAccess(AccountInterface $account, array $context, $entity_bundle = NULL): AccessResult {
    return AccessResult::allowedIfHasPermission($account, 'administer ps_division entities');
  }

}
