<?php

declare(strict_types=1);

namespace Drupal\ps_agent\Form;

use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Form\FormStateInterface;

/**
 * Form controller for Agent edit forms.
 *
 * @see \Drupal\ps_agent\Entity\Agent
 */
final class AgentForm extends ContentEntityForm {

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state): int {
    /** @var \Drupal\ps_agent\Entity\AgentInterface $entity */
    $entity = $this->entity;

    $result = parent::save($form, $form_state);

    $message_args = ['%label' => $entity->label()];

    if ($result === SAVED_NEW) {
      $this->messenger()->addStatus($this->t('Created new agent %label.', $message_args));
    }
    else {
      $this->messenger()->addStatus($this->t('Updated agent %label.', $message_args));
    }

    $form_state->setRedirectUrl($entity->toUrl('collection'));

    return $result;
  }

}
