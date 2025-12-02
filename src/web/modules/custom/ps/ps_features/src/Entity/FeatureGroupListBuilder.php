<?php

declare(strict_types=1);

namespace Drupal\ps_features\Entity;

use Drupal\Core\Config\Entity\ConfigEntityListBuilder;
use Drupal\Core\Entity\EntityInterface;

/**
 * List builder for Feature Group entities.
 *
 * Provides the admin UI at /admin/ps/features/groups for managing
 * feature groups. Displays groups in a table with drag-to-reorder.
 *
 * @see \Drupal\ps_features\Entity\FeatureGroup
 * @see docs/specs/04-ps-features.md#feature-groups
 */
class FeatureGroupListBuilder extends ConfigEntityListBuilder {

  /**
   * {@inheritdoc}
   */
  public function buildHeader(): array {
    $header = [
      'label' => $this->t('Label'),
      'id' => $this->t('Machine name'),
      'description' => $this->t('Description'),
      'icon' => $this->t('Icon'),
      'weight' => $this->t('Weight'),
    ];
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity): array {
    assert($entity instanceof FeatureGroupInterface);

    $row = [
      'label' => $entity->label(),
      'id' => $entity->id(),
      'description' => $entity->getDescription() ?? '',
      'icon' => $entity->getIcon() ?? $this->t('None'),
      'weight' => $entity->getWeight(),
    ];
    return $row + parent::buildRow($entity);
  }

  /**
   * {@inheritdoc}
   */
  public function load(): array {
    $entities = parent::load();

    // Sort by weight.
    uasort($entities, function ($a, $b) {
      return $a->getWeight() <=> $b->getWeight();
    });

    return $entities;
  }

}
