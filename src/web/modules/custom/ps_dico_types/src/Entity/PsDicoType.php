<?php

namespace Drupal\ps_dico_types\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBase;
use Drupal\ps_dico_types\PsDicoTypeInterface;

/**
 * Defines the Dictionary Type configuration entity (vocabulary-like).
 *
 * @ConfigEntityType(
 *   id = "ps_dico_type",
 *   label = @Translation("Dictionary Type"),
 *   label_collection = @Translation("Dictionary Types"),
 *   label_singular = @Translation("dictionary type"),
 *   label_plural = @Translation("dictionary types"),
 *   label_count = @PluralTranslation(
 *     singular = "@count dictionary type",
 *     plural = "@count dictionary types",
 *   ),
 *   handlers = {
 *     "list_builder" = "Drupal\ps_dico_types\PsDicoTypeListBuilder",
 *     "form" = {
 *       "add" = "Drupal\ps_dico_types\Form\PsDicoTypeForm",
 *       "edit" = "Drupal\ps_dico_types\Form\PsDicoTypeForm",
 *       "delete" = "Drupal\Core\Entity\EntityDeleteForm"
 *     }
 *   },
 *   admin_permission = "administer ps_dico_types",
 *   config_prefix = "type",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label",
 *     "status" = "status"
 *   },
 *   config_export = {
 *     "id",
 *     "label",
 *     "description",
 *     "status"
 *   },
 *   links = {
 *     "collection" = "/admin/structure/ps-dico-types",
 *     "add-form" = "/admin/structure/ps-dico-types/add",
 *     "edit-form" = "/admin/structure/ps-dico-types/{ps_dico_type}/edit",
 *     "delete-form" = "/admin/structure/ps-dico-types/{ps_dico_type}/delete",
 *     "dico-collection" = "/admin/structure/ps-dico-types/{ps_dico_type}/dicos"
 *   }
 * )
 */
class PsDicoType extends ConfigEntityBase implements PsDicoTypeInterface {

  /**
   * The dictionary type ID.
   *
   * @var string
   */
  protected $id;

  /**
   * The dictionary type label.
   *
   * @var string
   */
  protected $label;

  /**
   * The dictionary type description.
   *
   * @var string
   */
  protected $description;

  /**
   * {@inheritdoc}
   */
  public function getDescription(): string {
    return $this->description ?? '';
  }

  /**
   * {@inheritdoc}
   */
  public function setDescription(string $description): PsDicoTypeInterface {
    $this->description = $description;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheTags() {
    $tags = parent::getCacheTags();
    // Add custom cache tag for this dictionary type.
    $tags[] = 'ps_dico_type:' . $this->id();
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
