<?php

declare(strict_types=1);

namespace Drupal\ps_dictionary;

use Drupal\Core\Config\Entity\DraggableListBuilder;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Form\FormStateInterface;

/**
 * Provides a listing of Dictionary Entry entities.
 */
class DictionaryEntryListBuilder extends DraggableListBuilder {

  /**
   * The dictionary type to filter by.
   */
  protected ?string $dictionaryType = NULL;

  /**
   * Sets the dictionary type filter.
   *
   * @param string $type
   *   The dictionary type ID.
   *
   * @return $this
   */
  public function setDictionaryType(string $type): static {
    $this->dictionaryType = $type;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function load(): array {
    $entities = parent::load();

    // Filter by dictionary type if set.
    if ($this->dictionaryType) {
      $entities = array_filter($entities, function ($entity) {
        /** @var \Drupal\ps_dictionary\Entity\DictionaryEntryInterface $entity */
        return $entity->getDictionaryType() === $this->dictionaryType;
      });
    }

    return $entities;
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId(): string {
    return 'ps_dictionary_entry_list';
  }

  /**
   * {@inheritdoc}
   */
  public function buildHeader(): array {
    $header = parent::buildHeader();

    // Remove operations temporarily to reorder.
    $operations = $header['operations'] ?? [];
    unset($header['operations']);

    // Add label after weight.
    $header['label'] = $this->t('Label');

    // Add custom columns.
    $header['code'] = $this->t('Code');
    $header['status'] = $this->t('Status');
    $header['deprecated'] = $this->t('Deprecated');

    // Re-add operations at the end.
    $header['operations'] = $operations;

    return $header;
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity): array {
    /** @var \Drupal\ps_dictionary\Entity\DictionaryEntryInterface $entity */
    // Add custom columns first.
    $row['label'] = $entity->label();
    $row['code'] = [
      '#markup' => '<code>' . $entity->getCode() . '</code>',
    ];
    $row['status'] = [
      '#markup' => $entity->isActive() ? $this->t('Active') : $this->t('Inactive'),
    ];
    $row['deprecated'] = [
      '#markup' => $entity->isDeprecated() ? $this->t('Yes') : $this->t('No'),
    ];

    // Add parent row last (includes drag handle, weight, operations).
    return $row + parent::buildRow($entity);
  }

  /**
   * {@inheritdoc}
   */
  public function getDefaultOperations(EntityInterface $entity): array {
    /** @var \Drupal\ps_dictionary\Entity\DictionaryEntryInterface $entity */
    $operations = parent::getDefaultOperations($entity);

    // Add ps_dictionary_type parameter to all operation links.
    $dictionary_type = $entity->getDictionaryType();
    foreach ($operations as $key => $operation) {
      if (isset($operation['url'])) {
        $operations[$key]['url']->setRouteParameter('ps_dictionary_type', $dictionary_type);
      }
    }

    return $operations;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state): void {
    parent::submitForm($form, $form_state);

    // Clear cache for this dictionary type.
    $type = \Drupal::routeMatch()->getParameter('ps_dictionary_type');
    if ($type) {
      \Drupal::service('ps_dictionary.manager')->clearCache($type->id());
    }
  }

}
