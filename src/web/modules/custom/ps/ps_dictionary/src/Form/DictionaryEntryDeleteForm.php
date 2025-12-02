<?php

declare(strict_types=1);

namespace Drupal\ps_dictionary\Form;

use Drupal\Core\Entity\EntityConfirmFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\ps_dictionary\Service\DictionaryManagerInterface;

/**
 * Builds the form to delete Dictionary Entry entities.
 */
class DictionaryEntryDeleteForm extends EntityConfirmFormBase {

  /**
   * Constructs a DictionaryEntryDeleteForm.
   *
   * @param \Drupal\ps_dictionary\Service\DictionaryManagerInterface $dictionaryManager
   *   The dictionary manager service.
   */
  public function __construct(
    private readonly DictionaryManagerInterface $dictionaryManager,
  ) {}

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): static {
    $instance = parent::create($container);
    $instance->dictionaryManager = $container->get('ps_dictionary.manager');
    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  public function getQuestion(): string {
    return $this->t('Are you sure you want to delete %name?', [
      '%name' => $this->entity->label(),
    ]);
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelUrl(): Url {
    $dictionary_type = \Drupal::routeMatch()->getParameter('ps_dictionary_type');
    if ($dictionary_type) {
      return Url::fromRoute('ps_dictionary.entries', [
        'ps_dictionary_type' => $dictionary_type->id(),
      ]);
    }
    return new Url('entity.ps_dictionary_type.collection');
  }

  /**
   * {@inheritdoc}
   */
  public function getConfirmText(): string {
    return $this->t('Delete');
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state): void {
    /** @var \Drupal\ps_dictionary\Entity\DictionaryEntryInterface $entry */
    $entry = $this->entity;
    $type = $entry->getDictionaryType();

    $entry->delete();

    // Clear cache for this dictionary type.
    $this->dictionaryManager->clearCache($type);

    $this->messenger()->addStatus($this->t('Deleted the %label dictionary entry.', [
      '%label' => $entry->getLabel(),
    ]));

    $form_state->setRedirectUrl($this->getCancelUrl());
  }

}
