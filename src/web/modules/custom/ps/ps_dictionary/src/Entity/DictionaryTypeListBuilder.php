<?php

declare(strict_types=1);

namespace Drupal\ps_dictionary\Entity;

use Drupal\Core\Config\Entity\ConfigEntityListBuilder;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Url;

/**
 * List builder for dictionary types.
 *
 * @see docs/specs/03-ps-dictionary.md#dictionary-types
 */
final class DictionaryTypeListBuilder extends ConfigEntityListBuilder {

  /**
   * {@inheritdoc}
   */
  public function buildHeader(): array {
    $header['label'] = $this->t('Label');
    $header['id'] = $this->t('Machine name');
    $header['description'] = $this->t('Description');
    $header['entries'] = $this->t('Entries');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity): array {
    /** @var \Drupal\ps_dictionary\Entity\DictionaryTypeInterface $entity */
    $row['label'] = $entity->label();

    // Add locked badge if applicable.
    if ($entity->isLocked()) {
      $row['label'] = [
        'data' => [
          '#markup' => $entity->label() . ' ' . '<span class="badge badge--locked" style="background:#ffc107;color:#000;padding:2px 6px;border-radius:3px;font-size:0.75em;margin-left:4px;">' . $this->t('Locked') . '</span>',
        ],
      ];
    }

    $row['id'] = $entity->id();
    $row['description'] = method_exists($entity, 'getDescription') ? (string) $entity->getDescription() : '';
    $storage = \Drupal::entityTypeManager()->getStorage('ps_dictionary_entry');
    $query = $storage->getQuery();
    $query->condition('dictionary_type', $entity->id());
    $query->accessCheck(FALSE);
    $count = (int) $query->count()->execute();
    $row['entries'] = [
      'data' => [
        '#type' => 'link',
        '#title' => $this->t('@count entries', ['@count' => $count]),
        '#url' => Url::fromRoute('ps_dictionary.entries', ['ps_dictionary_type' => $entity->id()]),
      ],
    ];
    return $row + parent::buildRow($entity);
  }

  /**
   * {@inheritdoc}
   */
  public function getDefaultOperations(EntityInterface $entity): array {
    $operations = parent::getDefaultOperations($entity);
    $operations['entries'] = [
      'title' => $this->t('Entries'),
      'weight' => 15,
      'url' => Url::fromRoute('ps_dictionary.entries', ['ps_dictionary_type' => $entity->id()]),
    ];
    return $operations;
  }

}
