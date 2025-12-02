<?php

declare(strict_types=1);

namespace Drupal\ps_diagnostic\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBase;
use Drupal\Core\Entity\Attribute\ConfigEntityType;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\ps_diagnostic\Form\PsDiagnosticTypeDeleteForm;
use Drupal\ps_diagnostic\Form\PsDiagnosticTypeForm;
use Drupal\ps_diagnostic\PsDiagnosticTypeListBuilder;

/**
 * Defines the diagnostic type config entity.
 *
 * Stores configuration for regulatory diagnostic types (DPE, GES) including
 * energy classes with colors, ranges, and display settings.
 *
 * @see docs/specs/07-ps-diagnostic.md
 */
#[ConfigEntityType(
  id: 'ps_diagnostic_type',
  label: new TranslatableMarkup('Diagnostic Type'),
  label_collection: new TranslatableMarkup('Diagnostic Types'),
  label_singular: new TranslatableMarkup('diagnostic type'),
  label_plural: new TranslatableMarkup('diagnostic types'),
  label_count: [
    'singular' => '@count diagnostic type',
    'plural' => '@count diagnostic types',
  ],
  handlers: [
    'list_builder' => PsDiagnosticTypeListBuilder::class,
    'form' => [
      'add' => PsDiagnosticTypeForm::class,
      'edit' => PsDiagnosticTypeForm::class,
      'delete' => PsDiagnosticTypeDeleteForm::class,
    ],
  ],
  config_prefix: 'type',
  admin_permission: 'administer ps_diagnostic',
  entity_keys: [
    'id' => 'id',
    'label' => 'label',
  ],
  config_export: [
    'id',
    'label',
    'unit',
    'classes',
  ],
  links: [
    'collection' => '/admin/ps/structure/diagnostic-types',
    'add-form' => '/admin/ps/structure/diagnostic-types/add',
    'edit-form' => '/admin/ps/structure/diagnostic-types/{ps_diagnostic_type}/edit',
    'delete-form' => '/admin/ps/structure/diagnostic-types/{ps_diagnostic_type}/delete',
  ],
)]
class PsDiagnosticType extends ConfigEntityBase implements PsDiagnosticTypeInterface {

  /**
   * The diagnostic type ID.
   */
  protected string $id;

  /**
   * The diagnostic type label.
   *
   * @var \Drupal\Core\StringTranslation\TranslatableMarkup|string
   */
  protected TranslatableMarkup|string $label;

  /**
   * The unit of measurement.
   *
   * @var \Drupal\Core\StringTranslation\TranslatableMarkup|string
   */
  protected TranslatableMarkup|string $unit = '';

  /**
   * Energy classes configuration.
   *
   * @var array<string, array{label: string, color: string, range_max: int|null}>
   */
  protected array $classes = [];

  /**
   * {@inheritdoc}
   */
  public function label(): TranslatableMarkup|string {
    // Return translatable markup for UI display.
    if ($this->label instanceof TranslatableMarkup) {
      return $this->label;
    }
    return new TranslatableMarkup('@label', ['@label' => $this->label]);
  }

  /**
   * {@inheritdoc}
   */
  public function getLabel(): string {
    return (string) $this->label();
  }

  /**
   * {@inheritdoc}
   */
  public function getUnit(): string {
    if ($this->unit instanceof TranslatableMarkup) {
      return (string) $this->unit;
    }
    return (string) new TranslatableMarkup('@unit', ['@unit' => $this->unit]);
  }

  /**
   * {@inheritdoc}
   */
  public function setUnit(string $unit): static {
    $this->unit = $unit;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getClasses(): array {
    return $this->classes;
  }

  /**
   * {@inheritdoc}
   */
  public function setClasses(array $classes): static {
    $this->classes = $classes;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getClass(string $classCode): ?array {
    return $this->classes[$classCode] ?? NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function calculateClass(float $value): ?string {
    if ($value < 0) {
      return NULL;
    }

    $sortedClasses = $this->classes;
    ksort($sortedClasses);

    foreach ($sortedClasses as $code => $config) {
      if ($config['range_max'] === NULL) {
        return strtoupper($code);
      }
      if ($value <= $config['range_max']) {
        return strtoupper($code);
      }
    }

    // Return last class if value exceeds all ranges.
    return strtoupper((string) array_key_last($sortedClasses));
  }

}
