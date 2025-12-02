<?php

declare(strict_types=1);

namespace Drupal\ps_agent;

use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Access\AccessResult;
use Drupal\Core\Access\AccessResultInterface;

/**
 * Access controller for the Agent entity.
 *
 * @see \Drupal\ps_agent\Entity\Agent
 */
final class AgentAccessControlHandler extends EntityAccessControlHandler {

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account): AccessResultInterface {
    switch ($operation) {
      case 'view':
        return AccessResult::allowedIfHasPermission($account, 'view agent entities');

      case 'update':
        return AccessResult::allowedIfHasPermissions($account, [
          'edit agent entities',
          'administer agent entities',
        ], 'OR');

      case 'delete':
        return AccessResult::allowedIfHasPermissions($account, [
          'delete agent entities',
          'administer agent entities',
        ], 'OR');

      default:
        return AccessResult::neutral();
    }
  }

  /**
   * {@inheritdoc}
   */
  protected function checkCreateAccess(AccountInterface $account, array $context, $entity_bundle = NULL): AccessResultInterface {
    return AccessResult::allowedIfHasPermissions($account, [
      'create agent entities',
      'administer agent entities',
    ], 'OR');
  }

}
