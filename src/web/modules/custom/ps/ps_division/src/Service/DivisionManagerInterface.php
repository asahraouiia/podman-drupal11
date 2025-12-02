<?php

declare(strict_types=1);

namespace Drupal\ps_division\Service;

use Drupal\ps_division\Entity\DivisionInterface;

/**
 * Division manager interface (minimal model).
 *
 * Provides lightweight aggregation and validation utilities for divisions.
 *
 * @see docs/specs/08-ps-division.md#41-divisionmanager
 */
interface DivisionManagerInterface {

  /**
   * Loads divisions by weak parent entity ID (entity_id field).
   *
   * @param int $entityId
   *   Parent entity ID.
   *
   * @return array<int, \Drupal\ps_division\Entity\DivisionInterface>
   *   Loaded divisions keyed by ID.
   */
  public function getByParent(int $entityId): array;

  /**
   * Calculates total surface across all divisions for a parent entity.
   *
   * @param int $entityId
   *   Parent entity ID.
   *
   * @return float
   *   Total surface value.
   */
  public function calculateTotalSurface(int $entityId): float;

  /**
   * Validates a division entity (dictionary codes and surface values).
   *
   * @param \Drupal\ps_division\Entity\DivisionInterface $division
   *   Division entity to validate.
   *
   * @return array<string>
   *   Array of error messages (empty if valid).
   */
  public function validate(DivisionInterface $division): array;

  /**
   * Builds summary array for a division.
   *
   * @param \Drupal\ps_division\Entity\DivisionInterface $division
   *   Division entity.
   *
   * @return array<string, mixed>
   *   Summary with keys: id, building_name, type, nature, lot, total_surface.
   */
  public function getSummary(DivisionInterface $division): array;

}
