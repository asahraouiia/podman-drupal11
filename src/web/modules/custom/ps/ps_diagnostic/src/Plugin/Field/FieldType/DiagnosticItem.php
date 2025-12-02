<?php

declare(strict_types=1);

namespace Drupal\ps_diagnostic\Plugin\Field\FieldType;

use Drupal\Core\Field\Attribute\FieldType;
use Drupal\Core\Field\FieldItemBase;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\TypedData\DataDefinition;

/**
 * Defines the 'ps_diagnostic' field type.
 *
 * Stores regulatory diagnostic data (DPE, GES) with simplified structure:
 * - type_id: reference to PsDiagnosticType config entity
 * - value_numeric: numeric value for automatic class calculation
 * - label_code: energy class label (A-G, can be overridden)
 * - valid_from/valid_to: validity period (ISO 8601 dates)
 * - no_classification: boolean flag (displays "?" if true)
 * - non_applicable: boolean flag (displays "N/A" if true).
 *
 * @see docs/specs/07-ps-diagnostic.md
 * @see docs/02-modele-donnees-drupal.md#3-field-types-custom
 */
#[FieldType(
  id: 'ps_diagnostic',
  label: new TranslatableMarkup('Diagnostic'),
  description: new TranslatableMarkup('Stores regulatory diagnostic data (DPE, GES, technical indicators).'),
  category: 'PropertySearch',
  default_widget: 'ps_diagnostic_default',
  default_formatter: 'ps_diagnostic_default',
)]
class DiagnosticItem extends FieldItemBase {

  /**
   * {@inheritdoc}
   */
  public static function propertyDefinitions(FieldStorageDefinitionInterface $field_definition): array {
    $properties = [];

    $properties['type_id'] = DataDefinition::create('string')
      ->setLabel(new TranslatableMarkup('Diagnostic type ID'))
      ->setDescription(new TranslatableMarkup('Reference to diagnostic type config entity (dpe, ges, etc.).'))
      ->setRequired(FALSE);

    $properties['value_numeric'] = DataDefinition::create('float')
      ->setLabel(new TranslatableMarkup('Numeric value'))
      ->setDescription(new TranslatableMarkup('Numeric diagnostic value (used for automatic class calculation).'))
      ->setRequired(FALSE);

    $properties['label_code'] = DataDefinition::create('string')
      ->setLabel(new TranslatableMarkup('Label code'))
      ->setDescription(new TranslatableMarkup('Energy class label (A-G). Can be manually set or auto-calculated.'))
      ->setRequired(FALSE);

    $properties['valid_from'] = DataDefinition::create('string')
      ->setLabel(new TranslatableMarkup('Valid from'))
      ->setDescription(new TranslatableMarkup('Diagnostic date (ISO 8601: YYYY-MM-DD).'))
      ->setRequired(FALSE);

    $properties['valid_to'] = DataDefinition::create('string')
      ->setLabel(new TranslatableMarkup('Valid to'))
      ->setDescription(new TranslatableMarkup('Validity end date (ISO 8601: YYYY-MM-DD).'))
      ->setRequired(FALSE);

    $properties['no_classification'] = DataDefinition::create('boolean')
      ->setLabel(new TranslatableMarkup('No classification'))
      ->setDescription(new TranslatableMarkup('Displays "?" if no class can be determined.'))
      ->setRequired(FALSE);

    $properties['non_applicable'] = DataDefinition::create('boolean')
      ->setLabel(new TranslatableMarkup('Non applicable'))
      ->setDescription(new TranslatableMarkup('Displays "N/A" if diagnostic is not applicable.'))
      ->setRequired(FALSE);

    return $properties;
  }

  /**
   * {@inheritdoc}
   */
  public static function schema(FieldStorageDefinitionInterface $field_definition): array {
    return [
      'columns' => [
        'type_id' => [
          'type' => 'varchar',
          'length' => 50,
          'not null' => FALSE,
        ],
        'value_numeric' => [
          'type' => 'float',
          'size' => 'normal',
          'not null' => FALSE,
        ],
        'label_code' => [
          'type' => 'varchar',
          'length' => 10,
          'not null' => FALSE,
        ],
        'valid_from' => [
          'type' => 'varchar',
          'length' => 20,
          'not null' => FALSE,
        ],
        'valid_to' => [
          'type' => 'varchar',
          'length' => 20,
          'not null' => FALSE,
        ],
        'no_classification' => [
          'type' => 'int',
          'size' => 'tiny',
          'not null' => FALSE,
          'default' => 0,
        ],
        'non_applicable' => [
          'type' => 'int',
          'size' => 'tiny',
          'not null' => FALSE,
          'default' => 0,
        ],
      ],
      'indexes' => [
        'type_id' => ['type_id'],
        'label_code' => ['label_code'],
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function isEmpty(): bool {
    $typeId = $this->get('type_id')->getValue();
    $labelCode = $this->get('label_code')->getValue();
    $valueNumeric = $this->get('value_numeric')->getValue();
    $validFrom = $this->get('valid_from')->getValue();
    $validTo = $this->get('valid_to')->getValue();
    $noClassification = (bool) $this->get('no_classification')->getValue();
    $nonApplicable = (bool) $this->get('non_applicable')->getValue();

    // Treat empty string as NULL for numeric value.
    if ($valueNumeric === '') {
      $valueNumeric = NULL;
    }

    // Item considered empty when no core identifying data provided and no flags.
    return ($typeId === NULL || $typeId === '')
      && ($labelCode === NULL || $labelCode === '')
      && $valueNumeric === NULL
      && ($validFrom === NULL || $validFrom === '')
      && ($validTo === NULL || $validTo === '')
      && !$noClassification
      && !$nonApplicable;
  }

  /**
   * {@inheritdoc}
   */
  public function setValue($values, $notify = TRUE): void {
    // Normalise array values before parent processing.
    if (is_array($values)) {
      // Empty numeric value should become NULL (avoid primitive type error).
      if (array_key_exists('value_numeric', $values) && $values['value_numeric'] === '') {
        $values['value_numeric'] = NULL;
      }
      // Ensure numeric casting when provided.
      if (isset($values['value_numeric']) && $values['value_numeric'] !== NULL) {
        // Accept both string and numeric; cast safely.
        if ($values['value_numeric'] !== '' && is_numeric($values['value_numeric'])) {
          $values['value_numeric'] = (float) $values['value_numeric'];
        }
      }
      // Normalise label_code empty string to NULL for easier emptiness check.
      if (array_key_exists('label_code', $values) && $values['label_code'] === '') {
        $values['label_code'] = NULL;
      }
      // Normalise dates empty string to NULL.
      foreach (['valid_from', 'valid_to'] as $dateKey) {
        if (array_key_exists($dateKey, $values) && $values[$dateKey] === '') {
          $values[$dateKey] = NULL;
        }
      }
      // Booleans: cast truthy/falsy values explicitly.
      foreach (['no_classification', 'non_applicable'] as $boolKey) {
        if (array_key_exists($boolKey, $values)) {
          $values[$boolKey] = (bool) $values[$boolKey];
        }
      }
    }
    parent::setValue($values, $notify);
  }

  /**
   * {@inheritdoc}
   */
  public function getConstraints(): array {
    $constraints = parent::getConstraints();

    // Add custom validation for type_id and label_code.
    $constraints[] = $this->getTypedDataManager()
      ->getValidationConstraintManager()
      ->create('ComplexData', [
        'type_id' => [
          'Callback' => [
            'callback' => [$this, 'validateTypeId'],
          ],
        ],
        'label_code' => [
          'Callback' => [
            'callback' => [$this, 'validateLabelCode'],
          ],
        ],
      ]);

    return $constraints;
  }

  /**
   * Validates type_id against existing PsDiagnosticType entities.
   *
   * @param string|null $typeId
   *   The type ID to validate.
   * @param \Symfony\Component\Validator\Context\ExecutionContextInterface $context
   *   The validation context.
   */
  public function validateTypeId(?string $typeId, $context): void {
    if (empty($typeId)) {
      return;
    }

    $storage = \Drupal::entityTypeManager()->getStorage('ps_diagnostic_type');
    $entity = $storage->load($typeId);
    
    if ($entity === NULL) {
      $context->addViolation('Invalid diagnostic type ID: @type_id', ['@type_id' => $typeId]);
    }
  }

  /**
   * Validates label_code is A-G.
   *
   * @param string|null $labelCode
   *   The label code to validate.
   * @param \Symfony\Component\Validator\Context\ExecutionContextInterface $context
   *   The validation context.
   */
  public function validateLabelCode(?string $labelCode, $context): void {
    if (empty($labelCode)) {
      return;
    }

    if (!in_array(strtoupper($labelCode), ['A', 'B', 'C', 'D', 'E', 'F', 'G'], TRUE)) {
      $context->addViolation('Invalid label code: @label_code (must be A-G)', ['@label_code' => $labelCode]);
    }
  }

}
