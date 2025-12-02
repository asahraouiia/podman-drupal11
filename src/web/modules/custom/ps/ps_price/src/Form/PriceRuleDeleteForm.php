<?php

declare(strict_types=1);

namespace Drupal\ps_price\Form;

use Drupal\Core\Entity\EntityConfirmFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;

/**
 * Provides a deletion confirmation form for Price rule.
 *
 * Prevents deletion of locked system rules to maintain data integrity.
 *
 * @see \Drupal\ps_price\Entity\PriceRule
 * @see docs/modules/ps_price.md#price-rules
 */
final class PriceRuleDeleteForm extends EntityConfirmFormBase {

  /**
   * {@inheritdoc}
   */
  public function getQuestion(): string {
    return $this->t('Are you sure you want to delete the price rule %label?', [
      '%label' => $this->entity->label(),
    ]);
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelUrl(): Url {
    return new Url('entity.ps_price_rule.collection');
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
  public function buildForm(array $form, FormStateInterface $form_state): array {
    /** @var \Drupal\ps_price\PriceRuleInterface $rule */
    $rule = $this->entity;

    // Prevent deletion of locked rules.
    if ($rule->isLocked()) {
      $this->messenger()->addError(
        $this->t('The price rule %label is locked and cannot be deleted.', [
          '%label' => $rule->label(),
        ])
      );

      $form['#title'] = $this->t('Cannot delete locked price rule');
      $form['description'] = [
        '#markup' => '<p>' . $this->t('This price rule is marked as locked and cannot be deleted to maintain system integrity.') . '</p>',
      ];

      $form['actions'] = [
        '#type' => 'actions',
        'cancel' => [
          '#type' => 'link',
          '#title' => $this->t('Back'),
          '#url' => $this->getCancelUrl(),
          '#attributes' => ['class' => ['button']],
        ],
      ];

      return $form;
    }

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state): void {
    /** @var \Drupal\ps_price\PriceRuleInterface $rule */
    $rule = $this->entity;

    // Double-check lock status before deletion.
    if ($rule->isLocked()) {
      $this->messenger()->addError(
        $this->t('Cannot delete locked price rule %label.', [
          '%label' => $rule->label(),
        ])
      );
      $form_state->setRedirectUrl($this->getCancelUrl());
      return;
    }

    $this->entity->delete();
    $this->messenger()->addStatus(
      $this->t('Price rule %label has been deleted.', [
        '%label' => $this->entity->label(),
      ])
    );

    $form_state->setRedirectUrl($this->getCancelUrl());
  }

}
