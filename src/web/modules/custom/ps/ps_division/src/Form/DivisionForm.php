<?php

declare(strict_types=1);

namespace Drupal\ps_division\Form;

use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Form\FormStateInterface;

/**
 * Minimal add/edit form for Division entities (regenerated model).
 *
 * Provides basic field widgets; surface validation delegated to field type.
 *
 * @see docs/specs/08-ps-division.md#7-ui-administration
 */
final class DivisionForm extends ContentEntityForm {

  /**
   * {@inheritdoc}
   *
   * @phpstan-return array<string, mixed>
   */
  public function form(array $form, FormStateInterface $form_state): array {
    $form = parent::form($form, $form_state);

    // Building name is the label field.
    if (isset($form['building_name'])) {
      $form['building_name']['#group'] = 'advanced';
      $form['building_name']['#weight'] = -10;
    }

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state): int {
    $entity = $this->getEntity();
    $status = parent::save($form, $form_state);

    if ($status === SAVED_NEW) {
      $this->messenger()->addStatus($this->t('Division %label created.', [
        '%label' => $entity->label(),
      ]));
    }
    else {
      $this->messenger()->addStatus($this->t('Division %label updated.', [
        '%label' => $entity->label(),
      ]));
    }

    $form_state->setRedirect('entity.ps_division.canonical', ['ps_division' => $entity->id()]);

    return $status;
  }

}
