<?php

declare(strict_types=1);

namespace Drupal\ps_diagnostic\Service;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\ps_diagnostic\Entity\PsDiagnosticTypeInterface;

/**
 * Service for calculating diagnostic energy classes.
 *
 * Uses PsDiagnosticType configuration to determine class from value.
 * Handles special states (no_classification, non_applicable).
 *
 * @see docs/specs/07-ps-diagnostic.md
 */
final class DiagnosticClassCalculator implements DiagnosticClassCalculatorInterface {

  /**
   * Constructs a DiagnosticClassCalculator.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   Entity type manager.
   * @param \Drupal\Core\Logger\LoggerChannelFactoryInterface $loggerFactory
   *   Logger factory.
   */
  public function __construct(
    private readonly EntityTypeManagerInterface $entityTypeManager,
    private readonly LoggerChannelFactoryInterface $loggerFactory,
  ) {}

  /**
   * {@inheritdoc}
   */
  public function calculateClass(string $typeId, float $value): ?string {
    if ($value < 0) {
      return NULL;
    }

    $diagnosticType = $this->loadDiagnosticType($typeId);
    if ($diagnosticType === NULL) {
      return NULL;
    }

    return $diagnosticType->calculateClass($value);
  }

  /**
   * {@inheritdoc}
   */
  public function getDisplayInfo(array $diagnosticData): array {
    // Default empty result.
    $result = [
      'class' => NULL,
      'color' => NULL,
      'unit' => NULL,
      'display_text' => '',
      'is_special' => FALSE,
    ];

    // Handle special states.
    if (!empty($diagnosticData['non_applicable'])) {
      $result['display_text'] = 'N/A';
      $result['is_special'] = TRUE;
      return $result;
    }

    if (!empty($diagnosticData['no_classification'])) {
      $result['display_text'] = '?';
      $result['is_special'] = TRUE;
      return $result;
    }

    // Get diagnostic type.
    $typeId = $diagnosticData['type_id'] ?? NULL;
    if (empty($typeId)) {
      return $result;
    }

    $diagnosticType = $this->loadDiagnosticType($typeId);
    if ($diagnosticType === NULL) {
      return $result;
    }

    $result['unit'] = $diagnosticType->getUnit();

    // Use manual label_code if provided, otherwise calculate.
    $labelCode = $diagnosticData['label_code'] ?? NULL;
    if (!empty($labelCode)) {
      $result['class'] = strtoupper($labelCode);
    }
    elseif (isset($diagnosticData['value_numeric']) && is_numeric($diagnosticData['value_numeric'])) {
      $result['class'] = $this->calculateClass($typeId, (float) $diagnosticData['value_numeric']);
    }

    // Get color for class.
    if ($result['class'] !== NULL) {
      $classConfig = $diagnosticType->getClass(strtolower($result['class']));
      if ($classConfig !== NULL) {
        $result['color'] = $classConfig['color'];
        $result['display_text'] = $result['class'];
      }
    }

    return $result;
  }

  /**
   * Loads a diagnostic type entity.
   *
   * @param string $typeId
   *   The type ID.
   *
   * @return \Drupal\ps_diagnostic\Entity\PsDiagnosticTypeInterface|null
   *   The entity or NULL if not found.
   */
  private function loadDiagnosticType(string $typeId): ?PsDiagnosticTypeInterface {
    try {
      $storage = $this->entityTypeManager->getStorage('ps_diagnostic_type');
      $entity = $storage->load($typeId);

      if ($entity instanceof PsDiagnosticTypeInterface) {
        return $entity;
      }

      $this->loggerFactory
        ->get('ps_diagnostic')
        ->warning('Diagnostic type @type_id not found.', ['@type_id' => $typeId]);

      return NULL;
    }
    catch (\Exception $e) {
      $this->loggerFactory
        ->get('ps_diagnostic')
        ->error('Error loading diagnostic type @type_id: @message', [
          '@type_id' => $typeId,
          '@message' => $e->getMessage(),
        ]);
      return NULL;
    }
  }

}
