<?php

declare(strict_types=1);

namespace Drupal\ps_agent\Service;

use Drupal\ps_agent\Entity\AgentInterface;

/**
 * Interface for Agent Manager service.
 *
 * Provides centralized management of agent entities including:
 * - Lookup by external_id, email
 * - Active agents filtering
 * - Display name formatting
 *
 * Performance: Cached queries for active agents list.
 *
 * @see \Drupal\ps_agent\Service\AgentManager
 * @see docs/modules/ps_agent.md#service-agentmanager
 */
interface AgentManagerInterface {

  /**
   * Gets all agents.
   *
   * @return array<int, \Drupal\ps_agent\Entity\AgentInterface>
   *   Array of agent entities, keyed by entity ID.
   */
  public function getActiveAgents(): array;

  /**
   * Gets an agent by external ID.
   *
   * @param string $externalId
   *   The CRM external ID.
   *
   * @return \Drupal\ps_agent\Entity\AgentInterface|null
   *   The agent entity or NULL if not found.
   */
  public function getAgentByExternalId(string $externalId): ?AgentInterface;

  /**
   * Gets an agent by email.
   *
   * @param string $email
   *   The email address.
   *
   * @return \Drupal\ps_agent\Entity\AgentInterface|null
   *   The agent entity or NULL if not found.
   */
  public function getAgentByEmail(string $email): ?AgentInterface;

  /**
   * Gets multiple agents by external IDs.
   *
   * @param array<string> $externalIds
   *   Array of CRM external IDs.
   *
   * @return array<string, \Drupal\ps_agent\Entity\AgentInterface>
   *   Array of agents keyed by external_id.
   */
  public function getAgentsByExternalIds(array $externalIds): array;

  /**
   * Checks if an agent exists by external ID.
   *
   * @param string $externalId
   *   The CRM external ID.
   *
   * @return bool
   *   TRUE if agent exists, FALSE otherwise.
   */
  public function agentExists(string $externalId): bool;

  /**
   * Gets the formatted display name for an agent.
   *
   * @param \Drupal\ps_agent\Entity\AgentInterface $agent
   *   The agent entity.
   *
   * @return string
   *   The formatted display name.
   */
  public function getFormattedName(AgentInterface $agent): string;

  /**
   * Gets the list of BO editable fields from config.
   *
   * @return array<string>
   *   Array of field names that are editable in BO.
   */
  public function getBoEditableFields(): array;

  /**
   * Checks if a field is editable in BO.
   *
   * @param string $fieldName
   *   The field name to check.
   *
   * @return bool
   *   TRUE if field is BO editable.
   */
  public function isBoEditableField(string $fieldName): bool;

}
