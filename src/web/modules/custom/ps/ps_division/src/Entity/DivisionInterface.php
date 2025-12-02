<?php

declare(strict_types=1);

namespace Drupal\ps_division\Entity;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityChangedInterface;

/**
 * Interface for Division content entity (minimal model).
 *
 * Represents a spatial subdivision with structural classification and surfaces.
 * All pricing, features, and diagnostics are excluded from this minimal scope.
 *
 * @see docs/specs/08-ps-division.md
 * @see docs/02-modele-donnees-drupal.md#42-entité-ps_division
 */
interface DivisionInterface extends ContentEntityInterface, EntityChangedInterface {

  /**
   * Gets the weak parent entity ID (nullable reference).
   *
   * @return int|null
   *   Parent entity ID or NULL.
   */
  public function getEntityId(): ?int;

  /**
   * Sets the weak parent entity ID.
   *
   * @param int|null $entity_id
   *   Parent entity ID or NULL.
   *
   * @return static
   */
  public function setEntityId(?int $entity_id): static;

  /**
   * Gets the building name (entity label).
   *
   * @return string
   *   Building name.
   */
  public function getBuildingName(): string;

  /**
   * Sets the building name (entity label).
   *
   * @param string $name
   *   Building name.
   *
   * @return static
   */
  public function setBuildingName(string $name): static;

  /**
   * Gets the lot identifier.
   *
   * @return string|null
   *   Lot identifier or NULL.
   */
  public function getLot(): ?string;

  /**
   * Sets the lot identifier.
   *
   * @param string|null $lot
   *   Lot identifier or NULL.
   *
   * @return static
   */
  public function setLot(?string $lot): static;

  /**
   * Gets availability notes (translatable).
   *
   * @return string|null
   *   Availability text or NULL.
   */
  public function getAvailability(): ?string;

  /**
   * Sets availability notes.
   *
   * @param string|null $availability
   *   Availability text or NULL.
   *
   * @return static
   */
  public function setAvailability(?string $availability): static;

  /**
   * Computes total surface value (sum of all surfaces).
   *
   * Returns raw numeric sum without unit.
   *
   * @return float
   *   Total surface value.
   */
  public function getTotalSurface(): float;

}
