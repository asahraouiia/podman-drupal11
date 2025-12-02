<?php

declare(strict_types=1);

namespace Drupal\ps_diagnostic;

use Drupal\Core\Config\Entity\ConfigEntityListBuilder;
use Drupal\Core\Entity\EntityInterface;
use Drupal\ps_diagnostic\Entity\PsDiagnosticTypeInterface;

/**
 * Provides a listing of diagnostic types.
 *
 * @see docs/specs/07-ps-diagnostic.md
 */
class PsDiagnosticTypeListBuilder extends ConfigEntityListBuilder {

  /**
   * {@inheritdoc}
   */
  public function buildHeader(): array {
    $header['label'] = $this->t('Label');
    $header['id'] = $this->t('Machine name');
    $header['unit'] = $this->t('Unit');
    $header['classes'] = $this->t('Classes');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity): array {
    assert($entity instanceof PsDiagnosticTypeInterface);

    $row['label'] = $entity->label();
    $row['id'] = $entity->id();
    $row['unit'] = $entity->getUnit();
    $row['classes'] = implode(', ', array_keys($entity->getClasses()));

    return $row + parent::buildRow($entity);
  }

}
