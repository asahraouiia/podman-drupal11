<?php

declare(strict_types=1);

namespace Drupal\ps_diagnostic\Form;

use Drupal\Core\Entity\EntityConfirmFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;

/**
 * Provides a form for deleting a diagnostic type.
 *
 * @see docs/specs/07-ps-diagnostic.md
 */
class PsDiagnosticTypeDeleteForm extends EntityConfirmFormBase {

  /**
   * {@inheritdoc}
   */
  public function getQuestion(): string {
    return $this->t('Are you sure you want to delete the diagnostic type %label?', [
      '%label' => $this->entity->label(),
    ]);
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelUrl(): Url {
    return new Url('entity.ps_diagnostic_type.collection');
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
    $this->messenger()->addStatus($this->t('Deleted the %label diagnostic type.', [
      '%label' => $this->entity->label(),
    ]));

    $form_state->setRedirectUrl($this->getCancelUrl());
  }

}
