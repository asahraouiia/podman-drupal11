<?php

declare(strict_types=1);

namespace Drupal\ps_dictionary\Form;

use Drupal\Core\Entity\EntityConfirmFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;

/**
 * Builds the form to delete Dictionary Type entities.
 */
class DictionaryTypeDeleteForm extends EntityConfirmFormBase {

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state): array {
    /** @var \Drupal\ps_dictionary\Entity\DictionaryTypeInterface $entity */
    $entity = $this->entity;

    if ($entity->isLocked()) {
      $this->messenger()->addError($this->t('The dictionary type %label is locked and cannot be deleted.', [
        '%label' => $entity->label(),
      ]));
      return [
        '#markup' => $this->t('This dictionary type is locked and cannot be deleted.'),
        'actions' => [
          '#type' => 'actions',
          'cancel' => [
            '#type' => 'link',
            '#title' => $this->t('Back to list'),
            '#url' => $this->getCancelUrl(),
            '#attributes' => ['class' => ['button']],
          ],
        ],
      ];
    }

    return parent::buildForm($form, $form_state);
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
    $this->entity->delete();

    $this->messenger()->addStatus($this->t('Deleted the %label dictionary type.', [
      '%label' => $this->entity->label(),
    ]));

    $form_state->setRedirectUrl($this->getCancelUrl());
  }

}
