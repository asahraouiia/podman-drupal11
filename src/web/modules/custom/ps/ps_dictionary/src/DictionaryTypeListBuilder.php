<?php

declare(strict_types=1);

namespace Drupal\ps_dictionary;

use Drupal\Core\Config\Entity\ConfigEntityListBuilder;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Url;

/**
 * List builder for dictionary types.
 *
 * Adds a clickable entries count (links to entries list) and an
 * additional "Entries" operation while keeping "Edit" primary.
 *
 * @category PropertySearch
 * @package PsDictionary
 * @license https://example.com/license Proprietary
 * @link docs/specs/03-ps-dictionary.md#dictionary-types
 * @see \Drupal\ps_dictionary\Entity\DictionaryTypeInterface
 */
final class DictionaryTypeListBuilder extends ConfigEntityListBuilder {

  /**
   * {@inheritdoc}
   *
   * @return array
   *   Header definitions.
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
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The dictionary type entity being rendered in the table.
   *
   * @return array
   *   Render array for the row.
   */
  public function buildRow(EntityInterface $entity): array {
    $row['label'] = $entity->label();
    $row['id'] = $entity->id();
    // Description not guaranteed; safely derive or fallback to empty string.
    if (method_exists($entity, 'getDescription')) {
      $row['description'] = (string) $entity->getDescription();
    }
    else {
      $row['description'] = '';
    }

    $storage = \Drupal::entityTypeManager()->getStorage('ps_dictionary_entry');
    $query = $storage->getQuery();
    $query->condition('dictionary_type', $entity->id());
    $query->accessCheck(FALSE);
    $count = (int) $query->count()->execute();

    $row['entries'] = [
      'data' => [
        '#type' => 'link',
        '#title' => $this->t('@count entries', ['@count' => $count]),
        '#url' => Url::fromRoute(
                  'ps_dictionary.entries',
                  [
                    'ps_dictionary_type' => $entity->id(),
                  ]
        ),
      ],
    ];

    return $row + parent::buildRow($entity);
  }

  /**
   * {@inheritdoc}
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The dictionary type entity for which operations are generated.
   *
   * @return array
   *   Operations definitions.
   */
  public function getDefaultOperations(EntityInterface $entity): array {
    $operations = parent::getDefaultOperations($entity);
    $operations['entries'] = [
      'title' => $this->t('Entries'),
      'weight' => 15,
      'url' => Url::fromRoute(
              'ps_dictionary.entries',
              [
                'ps_dictionary_type' => $entity->id(),
              ]
      ),
    ];
    return $operations;
  }

}
