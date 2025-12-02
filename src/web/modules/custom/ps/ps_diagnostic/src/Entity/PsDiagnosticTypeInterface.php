<?php

declare(strict_types=1);

namespace Drupal\ps_diagnostic\Entity;

use Drupal\Core\Config\Entity\ConfigEntityInterface;

/**
 * Provides an interface for diagnostic type config entities.
 *
 * Defines regulatory diagnostic types (DPE, GES, etc.) with:
 * - Energy classes (A-G) with colors and ranges
 * - Unit of measurement
 * - Display configuration.
 *
 * @see docs/specs/07-ps-diagnostic.md
 */
interface PsDiagnosticTypeInterface extends ConfigEntityInterface {

  /**
   * Gets the diagnostic type label.
   *
   * @return string
   *   The label (e.g., "Consommations énergétiques").
   */
  public function getLabel(): string;

  /**
   * Gets the unit of measurement.
   *
   * @return string
   *   The unit (e.g., "kWh/m²/an").
   */
  public function getUnit(): string;

  /**
   * Sets the unit of measurement.
   *
   * @param string $unit
   *   The unit.
   *
   * @return $this
   */
  public function setUnit(string $unit): static;

  /**
   * Gets all energy classes configuration.
   *
   * @return array<string, array{label: string, color: string, range_max: int|null}>
   *   Array keyed by class code (a-g) with label, color, range_max.
   */
  public function getClasses(): array;

  /**
   * Sets energy classes configuration.
   *
   * @param array<string, array{label: string, color: string, range_max: int|null}> $classes
   *   Classes configuration.
   *
   * @return $this
   */
  public function setClasses(array $classes): static;

  /**
   * Gets a specific class configuration.
   *
   * @param string $classCode
   *   The class code (a-g).
   *
   * @return array{label: string, color: string, range_max: int|null}|null
   *   Class configuration or NULL if not found.
   */
  public function getClass(string $classCode): ?array;

  /**
   * Calculates the energy class based on numeric value.
   *
   * @param float $value
   *   The numeric value to classify.
   *
   * @return string|null
   *   The class code (A-G) or NULL if value is invalid.
   */
  public function calculateClass(float $value): ?string;

}
