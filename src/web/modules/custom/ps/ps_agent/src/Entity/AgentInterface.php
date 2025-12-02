<?php

declare(strict_types=1);

namespace Drupal\ps_agent\Entity;

use Drupal\Core\Entity\ContentEntityInterface;

/**
 * Provides an interface for Agent entities.
 *
 * @see \Drupal\ps_agent\Entity\Agent
 * @see docs/modules/ps_agent.md
 * @see docs/02-modele-donnees-drupal.md#43-entité-ps_agent
 */
interface AgentInterface extends ContentEntityInterface {

  /**
   * Gets the external ID from CRM.
   *
   * @return string|null
   *   The external CRM identifier.
   */
  public function getExternalId(): ?string;

  /**
   * Sets the external ID.
   *
   * @param string $externalId
   *   The external CRM identifier.
   *
   * @return $this
   */
  public function setExternalId(string $externalId): static;

  /**
   * Gets the agent's first name.
   *
   * @return string|null
   *   The first name.
   */
  public function getFirstName(): ?string;

  /**
   * Gets the agent's last name.
   *
   * @return string|null
   *   The last name.
   */
  public function getLastName(): ?string;

  /**
   * Gets the agent's email.
   *
   * @return string|null
   *   The email address.
   */
  public function getEmail(): ?string;

  /**
   * Gets the agent's phone number.
   *
   * @return string|null
   *   The phone number.
   */
  public function getPhone(): ?string;

  /**
   * Gets the agent's fax number.
   *
   * @return string|null
   *   The fax number.
   */
  public function getFax(): ?string;

  /**
   * Gets the creation timestamp.
   *
   * @return int
   *   The creation timestamp.
   */
  public function getCreatedTime(): int;

  /**
   * Gets the last modification timestamp.
   *
   * @return int
   *   The modification timestamp.
   */
  public function getChangedTime(): int;

}
