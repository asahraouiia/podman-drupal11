<?php

declare(strict_types=1);

namespace Drupal\ps_price\Form;

use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Form\FormStateInterface;
use Drupal\ps_dictionary\Service\DictionaryManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Form controller for the price rule entity edit forms.
 *
 * Provides administration interface for creating and editing price rules
 * with transaction type, unit, period, currency, and flags configuration.
 *
 * @see \Drupal\ps_price\Entity\PriceRule
 * @see docs/modules/ps_price.md#price-rules
 */
final class PriceRuleForm extends EntityForm {

  /**
   * Constructs a PriceRuleForm object.
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
  public static function create(ContainerInterface $container): self {
    return new self(
      $container->get('ps_dictionary.manager'),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state): array {
    $form = parent::form($form, $form_state);

    /** @var \Drupal\ps_price\PriceRuleInterface $rule */
    $rule = $this->entity;

    $form['label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Label'),
      '#maxlength' => 255,
      '#default_value' => $rule->label(),
      '#description' => $this->t('Name of the price rule.'),
      '#required' => TRUE,
    ];

    $form['id'] = [
      '#type' => 'machine_name',
      '#default_value' => $rule->id(),
      '#machine_name' => [
        'exists' => [$this, 'exist'],
      ],
      '#disabled' => !$rule->isNew(),
    ];

    $form['transaction_type'] = [
      '#type' => 'select',
      '#title' => $this->t('Transaction Type'),
      '#options' => $this->dictionaryManager->getOptions('transaction_type'),
      '#default_value' => $rule->getTransactionType(),
      '#required' => TRUE,
      '#description' => $this->t('The transaction type this rule applies to.'),
    ];

    $form['unit_code'] = [
      '#type' => 'select',
      '#title' => $this->t('Price Unit'),
      '#options' => $this->dictionaryManager->getOptions('price_unit'),
      '#default_value' => $rule->getUnitCode(),
      '#required' => TRUE,
      '#description' => $this->t('Default unit for price calculation.'),
    ];

    $form['period_code'] = [
      '#type' => 'select',
      '#title' => $this->t('Price Period'),
      '#options' => $this->dictionaryManager->getOptions('price_period'),
      '#default_value' => $rule->getPeriodCode(),
      '#required' => TRUE,
      '#description' => $this->t('Default period for recurring prices.'),
    ];

    $form['currency_code'] = [
      '#type' => 'select',
      '#title' => $this->t('Currency'),
      '#options' => $this->dictionaryManager->getOptions('currency'),
      '#default_value' => $rule->getCurrencyCode(),
      '#required' => TRUE,
      '#description' => $this->t('Default currency.'),
    ];

    $form['is_vat_excluded'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('VAT Excluded (HT)'),
      '#default_value' => $rule->isVatExcluded(),
      '#description' => $this->t('Check if prices are expressed excluding VAT (Hors Taxes).'),
    ];

    $form['is_charges_included'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Charges Included (CC)'),
      '#default_value' => $rule->isChargesIncluded(),
      '#description' => $this->t('Check if charges are included in the price (Charges Comprises).'),
    ];

    $form['weight'] = [
      '#type' => 'number',
      '#title' => $this->t('Weight'),
      '#default_value' => $rule->getWeight(),
      '#description' => $this->t('Rules with lower weights are evaluated first.'),
    ];

    $form['locked'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Locked'),
      '#default_value' => $rule->isLocked(),
      '#description' => $this->t('Locked rules cannot be deleted. System administrators only.'),
      '#access' => $this->currentUser()->hasPermission('administer ps_price configuration'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state): int {
    $result = parent::save($form, $form_state);
    $message_args = ['%label' => $this->entity->label()];
    $message = $result === SAVED_NEW
      ? $this->t('Created new price rule %label.', $message_args)
      : $this->t('Updated price rule %label.', $message_args);
    $this->messenger()->addStatus($message);
    $form_state->setRedirectUrl($this->entity->toUrl('collection'));
    return $result;
  }

  /**
   * Helper function to check whether a price rule configuration entity exists.
   *
   * @param string $id
   *   The entity ID.
   *
   * @return bool
   *   TRUE if the entity exists, FALSE otherwise.
   */
  public function exist(string $id): bool {
    $entity = $this->entityTypeManager->getStorage('ps_price_rule')->getQuery()
      ->condition('id', $id)
      ->execute();
    return (bool) $entity;
  }

}
