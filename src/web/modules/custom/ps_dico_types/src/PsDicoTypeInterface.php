<?php

namespace Drupal\ps_dico_types;

use Drupal\Core\Config\Entity\ConfigEntityInterface;

/**
 * Provides an interface defining a Dictionary Type entity.
 */
interface PsDicoTypeInterface extends ConfigEntityInterface {

  /**
   * Gets the description.
   *
   * @return string
   *   The description.
   */
  public function getDescription(): string;

  /**
   * Sets the description.
   *
   * @param string $description
   *   The description.
   *
   * @return \Drupal\ps_dico_types\PsDicoTypeInterface
   *   The dictionary type entity.
   */
  public function setDescription(string $description): PsDicoTypeInterface;

}
