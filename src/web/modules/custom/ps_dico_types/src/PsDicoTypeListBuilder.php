<?php

namespace Drupal\ps_dico_types;

use Drupal\Core\Config\Entity\ConfigEntityListBuilder;
use Drupal\Core\Entity\EntityInterface;

/**
 * Provides a listing of Dictionary Types.
 */
class PsDicoTypeListBuilder extends ConfigEntityListBuilder {

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $header['label'] = $this->t('Label');
    $header['id'] = $this->t('Machine name');
    $header['description'] = $this->t('Description');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    /** @var \Drupal\ps_dico_types\PsDicoTypeInterface $entity */
    $row['label'] = $entity->label();
    $row['id'] = $entity->id();
    $row['description'] = $entity->getDescription();
    return $row + parent::buildRow($entity);
  }

  /**
   * {@inheritdoc}
   */
  public function getDefaultOperations(EntityInterface $entity) {
    $operations = parent::getDefaultOperations($entity);

    // Add "Manage items" operation.
    $operations['manage_items'] = [
      'title' => $this->t('Manage items'),
      'weight' => 10,
      'url' => \Drupal\Core\Url::fromRoute('entity.ps_dico.type_collection', [
        'ps_dico_type' => $entity->id(),
      ]),
    ];

    return $operations;
  }

}
