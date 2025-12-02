<?php

declare(strict_types=1);

namespace Drupal\ps_agent\Service;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\ps\Service\SettingsManagerInterface;
use Drupal\ps_agent\Entity\AgentInterface;

/**
 * Agent Manager service.
 *
 * Provides centralized management of agent entities with CRM/BO field
 * protection, lookup operations, and business logic.
 *
 * @see \Drupal\ps_agent\Service\AgentManagerInterface
 * @see docs/modules/ps_agent.md
 */
final class AgentManager implements AgentManagerInterface {

  /**
   * Constructs a new AgentManager.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The entity type manager.
   * @param \Drupal\ps\Service\SettingsManagerInterface $settingsManager
   *   The PS settings manager.
   * @param \Drupal\Core\Logger\LoggerChannelFactoryInterface $loggerFactory
   *   The logger factory.
   */
  public function __construct(
    private readonly EntityTypeManagerInterface $entityTypeManager,
    private readonly SettingsManagerInterface $settingsManager,
    private readonly LoggerChannelFactoryInterface $loggerFactory,
  ) {}

  /**
   * {@inheritdoc}
   */
  public function getActiveAgents(): array {
    $storage = $this->entityTypeManager->getStorage('agent');

    $query = $storage->getQuery()
      ->accessCheck(TRUE)
      ->sort('last_name');

    $ids = $query->execute();

    if (empty($ids)) {
      return [];
    }

    /** @var array<int, \Drupal\ps_agent\Entity\AgentInterface> */
    return $storage->loadMultiple($ids);
  }

  /**
   * {@inheritdoc}
   */
  public function getAgentByExternalId(string $externalId): ?AgentInterface {
    $storage = $this->entityTypeManager->getStorage('agent');

    $query = $storage->getQuery()
      ->condition('external_id', $externalId)
      ->accessCheck(TRUE)
      ->range(0, 1);

    $ids = $query->execute();

    if (empty($ids)) {
      return NULL;
    }

    $id = reset($ids);
    /** @var \Drupal\ps_agent\Entity\AgentInterface|null */
    return $storage->load($id);
  }

  /**
   * {@inheritdoc}
   */
  public function getAgentByEmail(string $email): ?AgentInterface {
    $storage = $this->entityTypeManager->getStorage('agent');

    $query = $storage->getQuery()
      ->condition('email', $email)
      ->accessCheck(TRUE)
      ->range(0, 1);

    $ids = $query->execute();

    if (empty($ids)) {
      return NULL;
    }

    $id = reset($ids);
    /** @var \Drupal\ps_agent\Entity\AgentInterface|null */
    return $storage->load($id);
  }

  /**
   * {@inheritdoc}
   */
  public function getAgentsByExternalIds(array $externalIds): array {
    if (empty($externalIds)) {
      return [];
    }

    $storage = $this->entityTypeManager->getStorage('agent');

    $query = $storage->getQuery()
      ->condition('external_id', $externalIds, 'IN')
      ->accessCheck(TRUE);

    $ids = $query->execute();

    if (empty($ids)) {
      return [];
    }

    $agents = $storage->loadMultiple($ids);
    $result = [];

    /** @var \Drupal\ps_agent\Entity\AgentInterface $agent */
    foreach ($agents as $agent) {
      $externalId = $agent->getExternalId();
      if ($externalId !== NULL) {
        $result[$externalId] = $agent;
      }
    }

    return $result;
  }

  /**
   * {@inheritdoc}
   */
  public function agentExists(string $externalId): bool {
    return $this->getAgentByExternalId($externalId) !== NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function getFormattedName(AgentInterface $agent): string {
    return $agent->label();
  }

  /**
   * {@inheritdoc}
   */
  public function getBoEditableFields(): array {
    $config = $this->settingsManager->get('ps_agent.settings');
    $fields = $config['bo_editable_fields'] ?? ['email', 'phone', 'internal_notes'];

    return is_array($fields) ? array_filter($fields) : [];
  }

  /**
   * {@inheritdoc}
   */
  public function isBoEditableField(string $fieldName): bool {
    return in_array($fieldName, $this->getBoEditableFields(), TRUE);
  }

}
