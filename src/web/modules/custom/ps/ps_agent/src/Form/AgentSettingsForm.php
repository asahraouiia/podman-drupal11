<?php

declare(strict_types=1);

namespace Drupal\ps_agent\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Configure agent settings.
 */
final class AgentSettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId(): string {
    return 'ps_agent_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames(): array {
    return ['ps_agent.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state): array {
    $config = $this->config('ps_agent.settings');

    $form['crm_bo_split'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('CRM / BO Field Protection'),
      '#description' => $this->t('Configure which fields are editable in BO and preserved during CRM imports.'),
    ];

    $form['crm_bo_split']['bo_editable_fields'] = [
      '#type' => 'checkboxes',
      '#title' => $this->t('BO Editable Fields'),
      '#description' => $this->t('Select fields that can be edited in BO and will be preserved during CRM imports.'),
      '#options' => [
        'email' => $this->t('Email'),
        'phone' => $this->t('Phone'),
        'fax' => $this->t('Fax'),
      ],
      '#default_value' => $config->get('bo_editable_fields') ?? ['email', 'phone', 'fax'],
    ];

    $form['validation'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Validation Rules'),
    ];

    $form['validation']['require_email'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Email Required'),
      '#default_value' => $config->get('require_email') ?? TRUE,
    ];

    $form['validation']['require_phone'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Phone Required'),
      '#default_value' => $config->get('require_phone') ?? FALSE,
    ];

    $form['validation']['validate_phone_format'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Validate Phone Format'),
      '#description' => $this->t('Enable international phone format validation.'),
      '#default_value' => $config->get('validate_phone_format') ?? FALSE,
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state): void {
    $this->config('ps_agent.settings')
      ->set('bo_editable_fields', array_filter($form_state->getValue('bo_editable_fields')))
      ->set('require_email', $form_state->getValue('require_email'))
      ->set('require_phone', $form_state->getValue('require_phone'))
      ->set('validate_phone_format', $form_state->getValue('validate_phone_format'))
      ->save();

    parent::submitForm($form, $form_state);
  }

}
