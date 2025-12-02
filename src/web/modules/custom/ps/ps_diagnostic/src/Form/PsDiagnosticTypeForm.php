<?php

declare(strict_types=1);

namespace Drupal\ps_diagnostic\Form;

use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Form\FormStateInterface;

/**
 * Form handler for diagnostic type add and edit forms.
 *
 * Interactive table with drag & drop for energy classes configuration.
 *
 * @see docs/specs/07-ps-diagnostic.md
 */
class PsDiagnosticTypeForm extends EntityForm {

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state): array {
    $form = parent::form($form, $form_state);

    /** @var \Drupal\ps_diagnostic\Entity\PsDiagnosticTypeInterface $diagnosticType */
    $diagnosticType = $this->entity;

    // Attach library for enhanced styling.
    $form['#attached']['library'][] = 'ps_diagnostic/diagnostic_type_form';

    $form['label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Label'),
      '#maxlength' => 255,
      '#default_value' => $diagnosticType->label(),
      '#description' => $this->t('The human-readable name of this diagnostic type.'),
      '#required' => TRUE,
    ];

    $form['id'] = [
      '#type' => 'machine_name',
      '#default_value' => $diagnosticType->id(),
      '#machine_name' => [
        'exists' => [$this, 'exist'],
        'source' => ['label'],
      ],
      '#disabled' => !$diagnosticType->isNew(),
    ];

    $form['unit'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Unit'),
      '#maxlength' => 50,
      '#default_value' => $diagnosticType->getUnit(),
      '#description' => $this->t('Unit of measurement (e.g., kWh/m²/an, kg CO₂/m²/an).'),
      '#required' => TRUE,
    ];

    // Classes table with drag & drop.
    $form['classes_wrapper'] = [
      '#type' => 'details',
      '#title' => $this->t('Energy Classes Configuration'),
      '#open' => TRUE,
      '#prefix' => '<div id="classes-wrapper">',
      '#suffix' => '</div>',
      '#description' => $this->t('Define the energy classes with labels, colors, and maximum range values. Drag rows to reorder. The last class should have an empty range max.'),
    ];

    $form['classes_wrapper']['classes'] = [
      '#type' => 'table',
      '#header' => [
        $this->t('Label'),
        $this->t('Color'),
        $this->t('Range Max'),
        $this->t('Weight'),
        $this->t('Operations'),
      ],
      '#empty' => $this->t('No classes defined yet.'),
      '#tabledrag' => [
        [
          'action' => 'order',
          'relationship' => 'sibling',
          'group' => 'class-weight',
        ],
      ],
    ];

    $classes = $form_state->getValue('classes') ?? $diagnosticType->getClasses();
    if (empty($classes)) {
      // Default: 7 classes A-G for new types.
      $classes = [
        'a' => ['label' => 'A', 'color' => '#00A651', 'range_max' => 70],
        'b' => ['label' => 'B', 'color' => '#8DC63F', 'range_max' => 110],
        'c' => ['label' => 'C', 'color' => '#FFF200', 'range_max' => 180],
        'd' => ['label' => 'D', 'color' => '#F7941D', 'range_max' => 250],
        'e' => ['label' => 'E', 'color' => '#ED1C24', 'range_max' => 330],
        'f' => ['label' => 'F', 'color' => '#C1272D', 'range_max' => 420],
        'g' => ['label' => 'G', 'color' => '#A10D0D', 'range_max' => NULL],
      ];
    }

    $weight = 0;
    foreach ($classes as $code => $config) {
      $form['classes_wrapper']['classes'][$code]['#attributes']['class'][] = 'draggable';
      $form['classes_wrapper']['classes'][$code]['#weight'] = $weight;

      $form['classes_wrapper']['classes'][$code]['label'] = [
        '#type' => 'textfield',
        '#default_value' => $config['label'] ?? '',
        '#size' => 10,
        '#maxlength' => 10,
        '#required' => TRUE,
      ];

      $form['classes_wrapper']['classes'][$code]['color'] = [
        '#type' => 'color',
        '#default_value' => $config['color'] ?? '#FFFFFF',
        '#required' => TRUE,
      ];

      $form['classes_wrapper']['classes'][$code]['range_max'] = [
        '#type' => 'number',
        '#default_value' => $config['range_max'] ?? '',
        '#min' => 0,
        '#step' => 1,
        '#size' => 10,
        '#placeholder' => $this->t('Leave empty for last class'),
      ];

      $form['classes_wrapper']['classes'][$code]['weight'] = [
        '#type' => 'weight',
        '#title' => $this->t('Weight'),
        '#title_display' => 'invisible',
        '#default_value' => $weight,
        '#attributes' => ['class' => ['class-weight']],
      ];

      $form['classes_wrapper']['classes'][$code]['operations'] = [
        '#type' => 'submit',
        '#value' => $this->t('Remove'),
        '#name' => 'remove_class_' . $code,
        '#submit' => ['::removeClassSubmit'],
        '#ajax' => [
          'callback' => '::ajaxRebuildClasses',
          'wrapper' => 'classes-wrapper',
        ],
        '#limit_validation_errors' => [],
        '#class_code' => $code,
      ];

      $weight++;
    }

    $form['add_class'] = [
      '#type' => 'details',
      '#title' => $this->t('Add new class'),
      '#open' => FALSE,
    ];

    $form['add_class']['new_code'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Class code'),
      '#size' => 10,
      '#maxlength' => 20,
      '#placeholder' => 'h',
    ];

    $form['add_class']['add_button'] = [
      '#type' => 'submit',
      '#value' => $this->t('Add class'),
      '#submit' => ['::addClassSubmit'],
      '#ajax' => [
        'callback' => '::ajaxRebuildClasses',
        'wrapper' => 'classes-wrapper',
      ],
      '#limit_validation_errors' => [['add_class', 'new_code']],
    ];

    return $form;
  }

  /**
   * AJAX callback to rebuild classes table.
   */
  public function ajaxRebuildClasses(array &$form, FormStateInterface $form_state): array {
    return $form['classes_wrapper'];
  }

  /**
   * Submit handler to add a new class.
   */
  public function addClassSubmit(array &$form, FormStateInterface $form_state): void {
    $newCode = $form_state->getValue(['add_class', 'new_code']);
    if (!empty($newCode)) {
      $classes = $form_state->getValue(['classes_wrapper', 'classes']) ?? [];
      $classes[$newCode] = [
        'label' => strtoupper($newCode),
        'color' => '#CCCCCC',
        'range_max' => NULL,
        'weight' => count($classes),
      ];
      $form_state->setValue(['classes_wrapper', 'classes'], $classes);
    }
    $form_state->setRebuild();
  }

  /**
   * Submit handler to remove a class.
   */
  public function removeClassSubmit(array &$form, FormStateInterface $form_state): void {
    $trigger = $form_state->getTriggeringElement();
    $classCode = $trigger['#class_code'] ?? NULL;
    if ($classCode !== NULL) {
      $classes = $form_state->getValue(['classes_wrapper', 'classes']) ?? [];
      unset($classes[$classCode]);
      $form_state->setValue(['classes_wrapper', 'classes'], $classes);
    }
    $form_state->setRebuild();
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state): void {
    parent::validateForm($form, $form_state);

    $classes = $form_state->getValue(['classes_wrapper', 'classes']);
    if (empty($classes)) {
      $form_state->setErrorByName('classes', $this->t('At least one class must be defined.'));
      return;
    }

    // Validate color format.
    foreach ($classes as $code => $config) {
      if (!isset($config['label']) || trim($config['label']) === '') {
        $form_state->setErrorByName("classes_wrapper][classes][$code][label", $this->t('Label is required for class @code.', ['@code' => $code]));
      }
      if (!isset($config['color']) || !preg_match('/^#[0-9A-Fa-f]{6}$/', $config['color'])) {
        $form_state->setErrorByName("classes_wrapper][classes][$code][color", $this->t('Invalid color format for class @code. Use hex format (e.g., #00A651).', ['@code' => $code]));
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state): int {
    /** @var \Drupal\ps_diagnostic\Entity\PsDiagnosticTypeInterface $diagnosticType */
    $diagnosticType = $this->entity;

    // Process classes from table.
    $classes = $form_state->getValue(['classes_wrapper', 'classes']) ?? [];

    // Sort by weight and rebuild array.
    uasort($classes, function ($a, $b) {
      return ($a['weight'] ?? 0) <=> ($b['weight'] ?? 0);
    });

    // Clean up and format classes.
    $formattedClasses = [];
    foreach ($classes as $code => $config) {
      $formattedClasses[$code] = [
        'label' => trim($config['label']),
        'color' => strtoupper($config['color']),
        'range_max' => !empty($config['range_max']) ? (int) $config['range_max'] : NULL,
      ];
    }

    $diagnosticType->setClasses($formattedClasses);
    $status = $diagnosticType->save();

    if ($status === SAVED_NEW) {
      $this->messenger()->addStatus($this->t('Created the %label diagnostic type.', [
        '%label' => $diagnosticType->label(),
      ]));
    }
    else {
      $this->messenger()->addStatus($this->t('Updated the %label diagnostic type.', [
        '%label' => $diagnosticType->label(),
      ]));
    }

    $form_state->setRedirectUrl($diagnosticType->toUrl('collection'));

    return $status;
  }

  /**
   * Checks for an existing diagnostic type.
   *
   * @param string $id
   *   The machine name.
   *
   * @return bool
   *   TRUE if exists, FALSE otherwise.
   */
  public function exist(string $id): bool {
    $entity = $this->entityTypeManager
      ->getStorage('ps_diagnostic_type')
      ->getQuery()
      ->condition('id', $id)
      ->accessCheck(FALSE)
      ->execute();
    return (bool) $entity;
  }

}
