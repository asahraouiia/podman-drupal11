<?php

namespace Drupal\ps_dico_types\Form;

use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Messenger\MessengerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Form handler for Dictionary Item add and edit forms.
 */
class PsDicoForm extends EntityForm {

  /**
   * The messenger service.
   *
   * @var \Drupal\Core\Messenger\MessengerInterface
   */
  protected $messenger;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Constructs a PsDicoForm object.
   *
   * @param \Drupal\Core\Messenger\MessengerInterface $messenger
   *   The messenger service.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   */
  public function __construct(MessengerInterface $messenger, EntityTypeManagerInterface $entity_type_manager) {
    $this->messenger = $messenger;
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('messenger'),
      $container->get('entity_type.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);

    /** @var \Drupal\ps_dico_types\PsDicoInterface $entity */
    $entity = $this->entity;

    // Load available dictionary types.
    $types = $this->entityTypeManager->getStorage('ps_dico_type')->loadMultiple();
    $type_options = [];
    foreach ($types as $type) {
      $type_options[$type->id()] = $type->label();
    }

    $form['type'] = [
      '#type' => 'select',
      '#title' => $this->t('Dictionary Type'),
      '#options' => $type_options,
      '#default_value' => $entity->getType(),
      '#description' => $this->t('Select the dictionary type for this item.'),
      '#required' => TRUE,
      '#disabled' => !$entity->isNew(),
    ];

    $form['label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Label'),
      '#maxlength' => 255,
      '#default_value' => $entity->label(),
      '#description' => $this->t('The human-readable name of this dictionary item.'),
      '#required' => TRUE,
    ];

    $form['id'] = [
      '#type' => 'machine_name',
      '#default_value' => $entity->id(),
      '#machine_name' => [
        'exists' => [$this, 'exists'],
      ],
      '#disabled' => !$entity->isNew(),
    ];

    $form['weight'] = [
      '#type' => 'number',
      '#title' => $this->t('Weight'),
      '#default_value' => $entity->getWeight(),
      '#description' => $this->t('Heavier items sink and lighter items are positioned above. Items with equal weight are sorted alphabetically.'),
    ];

    return $form;
  }

  /**
   * Checks if a dictionary item exists.
   *
   * @param string $id
   *   The dictionary item ID.
   *
   * @return bool
   *   TRUE if the item exists, FALSE otherwise.
   */
  public function exists($id) {
    return (bool) $this->entityTypeManager
      ->getStorage('ps_dico')
      ->load($id);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);

    // Additional validation could be added here if needed.
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    /** @var \Drupal\ps_dico_types\PsDicoInterface $entity */
    $entity = $this->entity;
    $status = $entity->save();

    if ($status === SAVED_NEW) {
      $this->messenger->addStatus($this->t('Created the %label Dictionary Item.', [
        '%label' => $entity->label(),
      ]));
    }
    else {
      $this->messenger->addStatus($this->t('Updated the %label Dictionary Item.', [
        '%label' => $entity->label(),
      ]));
    }

    // Redirect to the type-specific collection if adding from type page.
    $type = $entity->getType();
    if ($type && $form_state->getValue('destination') === 'type_collection') {
      $form_state->setRedirect('entity.ps_dico.type_collection', ['ps_dico_type' => $type]);
    }
    else {
      $form_state->setRedirectUrl($entity->toUrl('collection'));
    }

    return $status;
  }

}
