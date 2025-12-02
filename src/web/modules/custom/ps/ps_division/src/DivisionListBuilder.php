<?php

declare(strict_types=1);

namespace Drupal\ps_division;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;
use Drupal\ps_division\Entity\DivisionInterface;

/**
 * List builder for Division entities (minimal columns).
 *
 * @see docs/specs/08-ps-division.md#7-ui-administration
 */
final class DivisionListBuilder extends EntityListBuilder {

  /**
   * {@inheritdoc}
   */
  public function buildHeader(): array {
    $header['id'] = $this->t('ID');
    $header['building_name'] = $this->t('Building');
    $header['entity_id'] = $this->t('Entity ID');
    $header['floor'] = $this->t('Floor');
    $header['type'] = $this->t('Type');
    $header['nature'] = $this->t('Nature');
    $header['lot'] = $this->t('Lot');
    $header['total_surface'] = $this->t('Total surface');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity): array {
    assert($entity instanceof DivisionInterface);

    $row['id'] = $entity->id();
    $row['building_name'] = $entity->toLink()->toString();
    $row['entity_id'] = $entity->getEntityId() ?? '-';
    $row['floor'] = $entity->get('floor')->value ?? '-';
    $row['type'] = $entity->get('type')->value ?? '-';
    $row['nature'] = $entity->get('nature')->value ?? '-';
    $row['lot'] = $entity->getLot() ?? '-';
    $row['total_surface'] = number_format($entity->getTotalSurface(), 2);

    return $row + parent::buildRow($entity);
  }

}
