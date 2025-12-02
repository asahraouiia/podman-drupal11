<?php

declare(strict_types=1);

namespace Drupal\ps\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Settings form for PropertySearch.
 */
class SettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames(): array {
    return ['ps.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId(): string {
    return 'ps_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state): array {
    $config = $this->config('ps.settings');

    $form['performance'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Performance Settings'),
    ];

    $form['performance']['enable_monitoring'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Enable performance monitoring'),
      '#default_value' => $config->get('performance.enable_monitoring'),
    ];

    $form['performance']['slow_request_threshold'] = [
      '#type' => 'number',
      '#title' => $this->t('Slow request threshold (ms)'),
      '#default_value' => $config->get('performance.slow_request_threshold'),
      '#min' => 100,
      '#max' => 10000,
    ];

    $form['validation'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Validation Settings'),
    ];

    $form['validation']['strict_mode'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Enable strict validation mode'),
      '#default_value' => $config->get('validation.strict_mode'),
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state): void {
    $this->config('ps.settings')
      ->set('performance.enable_monitoring', $form_state->getValue('enable_monitoring'))
      ->set('performance.slow_request_threshold', $form_state->getValue('slow_request_threshold'))
      ->set('validation.strict_mode', $form_state->getValue('strict_mode'))
      ->save();

    parent::submitForm($form, $form_state);
  }

}
