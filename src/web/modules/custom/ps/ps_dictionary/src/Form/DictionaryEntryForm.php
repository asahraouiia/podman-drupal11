<?php

declare(strict_types=1);

namespace Drupal\ps_dictionary\Form;

use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\ps_dictionary\Service\DictionaryManagerInterface;

/**
 * Form handler for Dictionary Entry add and edit forms.
 */
class DictionaryEntryForm extends EntityForm {

  /**
   * Constructs a DictionaryEntryForm.
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
  public static function create(ContainerInterface $container): static {
    return new static(
      $container->get('ps_dictionary.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state): array {
    $form = parent::form($form, $form_state);

    /** @var \Drupal\ps_dictionary\Entity\DictionaryEntryInterface $entry */
    $entry = $this->entity;

    // Get dictionary type from route or entity.
    $dictionary_type = \Drupal::routeMatch()->getParameter('ps_dictionary_type');
    if ($dictionary_type && $entry->isNew()) {
      $type_id = is_string($dictionary_type) ?
        $dictionary_type : $dictionary_type->id();
      $entry->setDictionaryType($type_id);
    }

    $form['dictionary_type'] = [
      '#type' => 'value',
      '#value' => $entry->getDictionaryType(),
    ];

    $form['code'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Code'),
      '#maxlength' => 255,
      '#default_value' => $entry->getCode(),
      '#description' => $this->t('The machine code for this entry.'),
      '#required' => TRUE,
      '#disabled' => !$entry->isNew(),
    ];

    $form['label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Label'),
      '#maxlength' => 255,
      '#default_value' => $entry->getLabel(),
      '#description' => $this->t('The human-readable label for this entry.'),
      '#required' => TRUE,
    ];

    $form['description'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Description'),
      '#default_value' => $entry->getDescription(),
      '#description' => $this->t('A description of this entry.'),
    ];

    $form['weight'] = [
      '#type' => 'weight',
      '#title' => $this->t('Weight'),
      '#default_value' => $entry->getWeight(),
      '#delta' => 50,
      '#access' => FALSE,
    ];

    $form['status'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Active'),
      '#default_value' => $entry->isActive(),
      '#description' => $this->t('Only active entries appear in listings and forms.'),
    ];

    $form['deprecated'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Deprecated'),
      '#default_value' => $entry->isDeprecated(),
      '#description' => $this->t('Mark as deprecated (still valid but discouraged for new usage).'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state): int {
    /** @var \Drupal\ps_dictionary\Entity\DictionaryEntryInterface $entry */
    $entry = $this->entity;

    // Set ID based on type and code (snake_case).
    if ($entry->isNew()) {
      $id = $entry->getDictionaryType() . '_' . strtolower($entry->getCode());
      $entry->set('id', $id);
    }

    $status = $entry->save();

    // Clear cache for this dictionary type.
    $this->dictionaryManager->clearCache($entry->getDictionaryType());

    if ($status === SAVED_NEW) {
      $this->messenger()->addStatus($this->t('Created the %label dictionary entry.', [
        '%label' => $entry->getLabel(),
      ]));
    }
    else {
      $this->messenger()->addStatus($this->t('Saved the %label dictionary entry.', [
        '%label' => $entry->getLabel(),
      ]));
    }

    $dictionary_type = \Drupal::routeMatch()->getParameter('ps_dictionary_type');
    if ($dictionary_type) {
      $form_state->setRedirect('ps_dictionary.entries', [
        'ps_dictionary_type' => is_string($dictionary_type) ? $dictionary_type : $dictionary_type->id(),
      ]);
    }
    else {
      $form_state->setRedirect('entity.ps_dictionary_type.collection');
    }

    return $status;
  }

}
