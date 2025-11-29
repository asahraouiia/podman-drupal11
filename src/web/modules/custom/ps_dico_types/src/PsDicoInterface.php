<?php

namespace Drupal\ps_dico_types;

use Drupal\Core\Config\Entity\ConfigEntityInterface;

/**
 * Provides an interface defining a Dictionary Item entity.
 */
interface PsDicoInterface extends ConfigEntityInterface {

  /**
   * Gets the dictionary type ID.
   *
   * @return string
   *   The type ID.
   */
  public function getType(): string;

  /**
   * Sets the dictionary type ID.
   *
   * @param string $type
   *   The type ID.
   *
   * @return \Drupal\ps_dico_types\PsDicoInterface
   *   The dictionary item entity.
   */
  public function setType(string $type): PsDicoInterface;

  /**
   * Gets the weight.
   *
   * @return int
   *   The weight.
   */
  public function getWeight(): int;

  /**
   * Sets the weight.
   *
   * @param int $weight
   *   The weight.
   *
   * @return \Drupal\ps_dico_types\PsDicoInterface
   *   The dictionary item entity.
   */
  public function setWeight(int $weight): PsDicoInterface;

}
