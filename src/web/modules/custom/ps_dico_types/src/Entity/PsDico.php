<?php

namespace Drupal\ps_dico_types\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBase;
use Drupal\ps_dico_types\PsDicoInterface;

/**
 * Defines the Dictionary Item configuration entity (taxonomy-like).
 *
 * @ConfigEntityType(
 *   id = "ps_dico",
 *   label = @Translation("Dictionary Item"),
 *   label_collection = @Translation("Dictionary Items"),
 *   label_singular = @Translation("dictionary item"),
 *   label_plural = @Translation("dictionary items"),
 *   label_count = @PluralTranslation(
 *     singular = "@count dictionary item",
 *     plural = "@count dictionary items",
 *   ),
 *   handlers = {
 *     "list_builder" = "Drupal\ps_dico_types\PsDicoListBuilder",
 *     "form" = {
 *       "add" = "Drupal\ps_dico_types\Form\PsDicoForm",
 *       "edit" = "Drupal\ps_dico_types\Form\PsDicoForm",
 *       "delete" = "Drupal\Core\Entity\EntityDeleteForm"
 *     }
 *   },
 *   admin_permission = "administer ps_dico_types",
 *   config_prefix = "dico",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label",
 *     "weight" = "weight",
 *     "status" = "status"
 *   },
 *   config_export = {
 *     "id",
 *     "label",
 *     "type",
 *     "weight",
 *     "status"
 *   },
 *   links = {
 *     "collection" = "/admin/structure/ps-dico-items",
 *     "add-form" = "/admin/structure/ps-dico-items/add",
 *     "edit-form" = "/admin/structure/ps-dico-items/{ps_dico}/edit",
 *     "delete-form" = "/admin/structure/ps-dico-items/{ps_dico}/delete"
 *   }
 * )
 */
class PsDico extends ConfigEntityBase implements PsDicoInterface {

  /**
   * The dictionary item ID.
   *
   * @var string
   */
  protected $id;

  /**
   * The dictionary item label.
   *
   * @var string
   */
  protected $label;

  /**
   * The dictionary type ID.
   *
   * @var string
   */
  protected $type;

  /**
   * The weight of this dictionary item.
   *
   * @var int
   */
  protected $weight = 0;

  /**
   * {@inheritdoc}
   */
  public function getType(): string {
    return $this->type ?? '';
  }

  /**
   * {@inheritdoc}
   */
  public function setType(string $type): PsDicoInterface {
    $this->type = $type;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getWeight(): int {
    return (int) $this->weight;
  }

  /**
   * {@inheritdoc}
   */
  public function setWeight(int $weight): PsDicoInterface {
    $this->weight = $weight;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheTags() {
    $tags = parent::getCacheTags();
    // Add cache tag for parent type.
    if ($this->type) {
      $tags[] = 'ps_dico_type:' . $this->type;
    }
    // Add tag for this specific dictionary item.
    $tags[] = 'ps_dico:' . $this->id();
    return $tags;
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheMaxAge() {
    $settings_manager = \Drupal::service('ps_dico_types.settings_manager');
    return $settings_manager->getCacheTtl();
  }

}
