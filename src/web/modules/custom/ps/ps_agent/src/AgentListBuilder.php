<?php

declare(strict_types=1);

namespace Drupal\ps_agent;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;

/**
 * Defines a class to build a listing of Agent entities.
 *
 * @see \Drupal\ps_agent\Entity\Agent
 */
final class AgentListBuilder extends EntityListBuilder {

  /**
   * {@inheritdoc}
   */
  public function buildHeader(): array {
    $header['id'] = $this->t('ID');
    $header['name'] = $this->t('Name');
    $header['email'] = $this->t('Email');
    $header['phone'] = $this->t('Phone');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity): array {
    /** @var \Drupal\ps_agent\Entity\AgentInterface $entity */
    $row['id'] = $entity->id();
    $row['name'] = $entity->label();
    $row['email'] = $entity->getEmail();
    $row['phone'] = $entity->getPhone() ?? '-';
    return $row + parent::buildRow($entity);
  }

}
