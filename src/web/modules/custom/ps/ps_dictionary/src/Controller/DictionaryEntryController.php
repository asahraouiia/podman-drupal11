<?php

declare(strict_types=1);

namespace Drupal\ps_dictionary\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\ps_dictionary\Entity\DictionaryTypeInterface;

/**
 * Controller for dictionary entry operations.
 */
class DictionaryEntryController extends ControllerBase {

  /**
   * Lists dictionary entries for a given type.
   *
   * @param \Drupal\ps_dictionary\Entity\DictionaryTypeInterface $ps_dictionary_type
   *   The dictionary type.
   *
   * @return array
   *   A render array.
   */
  public function listEntries(DictionaryTypeInterface $ps_dictionary_type): array {
    /** @var \Drupal\ps_dictionary\Entity\DictionaryEntryListBuilder $list_builder */
    $list_builder = $this->entityTypeManager()->getListBuilder('ps_dictionary_entry');
    $list_builder->setDictionaryType($ps_dictionary_type->id());

    return [
      '#type' => 'container',
      'table' => $list_builder->render(),
      '#cache' => [
        'tags' => ['ps_dictionary:' . $ps_dictionary_type->id()],
      ],
    ];
  }

  /**
   * Title callback for the entries list.
   *
   * @param \Drupal\ps_dictionary\Entity\DictionaryTypeInterface $ps_dictionary_type
   *   The dictionary type.
   *
   * @return \Drupal\Core\StringTranslation\TranslatableMarkup
   *   The page title.
   */
  public function getTitle(DictionaryTypeInterface $ps_dictionary_type): TranslatableMarkup {
    return $this->t('Entries: @label', [
      '@label' => $ps_dictionary_type->label(),
    ]);
  }

}
