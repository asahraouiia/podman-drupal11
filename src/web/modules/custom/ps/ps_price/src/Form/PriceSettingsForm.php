<?php

declare(strict_types=1);

namespace Drupal\ps_price\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\ps_dictionary\Service\DictionaryManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Settings form for PS Price.
 *
 * Provides configuration for:
 * - Default currency
 *
 * Transaction type rules are now managed via PriceRule config entities.
 *
 * @see \Drupal\ps_price\Entity\PriceRule
 * @see docs/modules/ps_price.md#settings
 */
final class PriceSettingsForm extends ConfigFormBase {

  /**
   * Constructs a PriceSettingsForm.
   */
  public function __construct(
    private readonly DictionaryManagerInterface $dictionaryManager,
  ) {
  }

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
  public function getFormId(): string {
    return 'ps_price_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames(): array {
    return ['ps_price.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state): array {
    $config = $this->config('ps_price.settings');

    // Default currency.
    $form['default_currency'] = [
      '#type' => 'select',
      '#title' => $this->t('Default currency'),
      '#options' => $this->dictionaryManager->getOptions('currency'),
      '#default_value' => $config->get('default_currency') ?? 'EUR',
      '#required' => TRUE,
      '#description' => $this->t('Currency used by default for price display and input.'),
    ];

    $form['rules_notice'] = [
      '#type' => 'markup',
      '#markup' => '<div class="messages messages--info">' .
        $this->t('Transaction type rules are now managed via <a href="@url">Price Rules</a>.', [
          '@url' => '/admin/ps/config/price/rules',
        ]) .
        '</div>',
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state): void {
    $this->configFactory()->getEditable('ps_price.settings')
      ->set('default_currency', $form_state->getValue('default_currency'))
      ->save();

    parent::submitForm($form, $form_state);
  }

}
