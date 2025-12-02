<?php

declare(strict_types=1);

namespace Drupal\ps_diagnostic\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Configure Diagnostic settings.
 *
 * @see docs/specs/07-ps-diagnostic.md#5-configuration
 */
final class DiagnosticSettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames(): array {
    return ['ps_diagnostic.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId(): string {
    return 'ps_diagnostic_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state): array {
    $config = $this->config('ps_diagnostic.settings');

    $form['surface_round_precision'] = [
      '#type' => 'number',
      '#title' => $this->t('Surface rounding precision'),
      '#description' => $this->t('Number of decimal places for surface display.'),
      '#default_value' => $config->get('surface_round_precision') ?? 2,
      '#min' => 0,
      '#max' => 4,
      '#required' => TRUE,
    ];

    $form['enable_completeness_score'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Enable completeness score calculation'),
      '#description' => $this->t('Calculate and display diagnostic completeness scores (0-100).'),
      '#default_value' => $config->get('enable_completeness_score') ?? TRUE,
    ];

    $form['flags_definition'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Available compliance flags'),
      '#description' => $this->t('One flag code per line. Defines possible compliance flags.'),
      '#default_value' => implode("\n", (array) ($config->get('flags_definition') ?? [])),
      '#rows' => 10,
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state): void {
    $raw = $form_state->getValue('flags_definition');
    $flagsRaw = is_string($raw) ? $raw : '';
    $flags = array_filter(array_map('trim', explode("\n", $flagsRaw)));

    $precision = $form_state->getValue('surface_round_precision');
    $this->config('ps_diagnostic.settings')
      ->set('surface_round_precision', is_numeric($precision) ? (int) $precision : 0)
      ->set('enable_completeness_score', (bool) $form_state->getValue('enable_completeness_score'))
      ->set('flags_definition', array_values($flags))
      ->save();

    parent::submitForm($form, $form_state);
  }

}
