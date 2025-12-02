<?php

declare(strict_types=1);

namespace Drupal\ps_features;

use Drupal\Core\Config\Entity\ConfigEntityListBuilder;
use Drupal\Core\Entity\EntityInterface;

/**
 * Provides a listing of Feature entities.
 */
class FeatureListBuilder extends ConfigEntityListBuilder {

  /**
   * {@inheritdoc}
   */
  public function buildHeader(): array {
    $header['label'] = $this->t('Label');
    $header['id'] = $this->t('Machine name');
    $header['value_type'] = $this->t('Value Type');
    $header['group'] = $this->t('Group');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity): array {
    /** @var \Drupal\ps_features\Entity\FeatureInterface $entity */
    $row['label'] = $entity->label();
    $row['id'] = $entity->id();
    $row['value_type'] = $entity->getValueType();

    $metadata = $entity->getMetadata();
    $row['group'] = $metadata['group'] ?? '-';

    return $row + parent::buildRow($entity);
  }

}
