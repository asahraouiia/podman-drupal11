<?php

namespace Drupal\ps_dico_types;

use Drupal\Core\Config\Entity\ConfigEntityListBuilder;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a listing of Dictionary Items.
 */
class PsDicoListBuilder extends ConfigEntityListBuilder {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * {@inheritdoc}
   */
  public static function createInstance(ContainerInterface $container, EntityTypeInterface $entity_type) {
    $instance = parent::createInstance($container, $entity_type);
    $instance->entityTypeManager = $container->get('entity_type.manager');
    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $header['label'] = $this->t('Label');
    $header['id'] = $this->t('Machine name');
    $header['type'] = $this->t('Dictionary Type');
    $header['weight'] = $this->t('Weight');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    /** @var \Drupal\ps_dico_types\PsDicoInterface $entity */
    $row['label'] = $entity->label();
    $row['id'] = $entity->id();
    
    // Load and display the type label.
    $type_id = $entity->getType();
    $type = $this->entityTypeManager->getStorage('ps_dico_type')->load($type_id);
    $row['type'] = $type ? $type->label() : $this->t('- None -');
    
    $row['weight'] = $entity->getWeight();
    return $row + parent::buildRow($entity);
  }

  /**
   * {@inheritdoc}
   */
  public function load() {
    $entities = parent::load();
    
    // Sort by weight, then by label.
    uasort($entities, function ($a, $b) {
      $a_weight = $a->getWeight();
      $b_weight = $b->getWeight();
      
      if ($a_weight == $b_weight) {
        return strcasecmp($a->label(), $b->label());
      }
      
      return ($a_weight < $b_weight) ? -1 : 1;
    });
    
    return $entities;
  }

}
