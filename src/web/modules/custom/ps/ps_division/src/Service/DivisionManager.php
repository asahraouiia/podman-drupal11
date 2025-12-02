<?php

declare(strict_types=1);

namespace Drupal\ps_division\Service;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\Logger\LoggerChannelInterface;
use Drupal\ps_dictionary\Service\DictionaryManagerInterface;
use Drupal\ps_division\Entity\DivisionInterface;

/**
 * Division manager implementation (minimal regenerated model).
 *
 * @see \Drupal\ps_division\Service\DivisionManagerInterface
 * @see docs/specs/08-ps-division.md#41-divisionmanager
 */
final class DivisionManager implements DivisionManagerInterface {

  /**
   * Logger channel.
   *
   * @phpstan-ignore-next-line Logger prepared for future error logging
   */
  private readonly LoggerChannelInterface $logger;

  /**
   * Constructs a DivisionManager.
   */
  public function __construct(
    private readonly EntityTypeManagerInterface $entityTypeManager,
    private readonly DictionaryManagerInterface $dictionaryManager,
    private readonly CacheBackendInterface $cache,
    LoggerChannelFactoryInterface $loggerFactory,
  ) {
    $this->logger = $loggerFactory->get('ps_division');
  }

  /**
   * {@inheritdoc}
   *
   * @return array<int, \Drupal\ps_division\Entity\DivisionInterface>
   */
  public function getByParent(int $entityId): array {
    $cacheKey = "ps_division:parent:{$entityId}";
    if ($cached = $this->cache->get($cacheKey)) {
      if (is_array($cached->data)) {
        return $cached->data;
      }
    }

    $storage = $this->entityTypeManager->getStorage('ps_division');
    $query = $storage->getQuery()
      ->condition('entity_id', $entityId)
      ->accessCheck(FALSE)
      ->sort('id');
    $ids = $query->execute();

    /** @var array<int, \Drupal\ps_division\Entity\DivisionInterface> $divisions */
    $divisions = $storage->loadMultiple($ids);

    $this->cache->set($cacheKey, $divisions, CacheBackendInterface::CACHE_PERMANENT, [
      'ps_division_list',
      "ps_division_parent:{$entityId}",
    ]);

    return $divisions;
  }

  /**
   * {@inheritdoc}
   */
  public function calculateTotalSurface(int $entityId): float {
    $total = 0.0;
    foreach ($this->getByParent($entityId) as $division) {
      $total += $division->getTotalSurface();
    }
    return $total;
  }

  /**
   * {@inheritdoc}
   */
  public function validate(DivisionInterface $division): array {
    $errors = [];

    // Validate surfaces values and codes.
    if ($division->hasField('surfaces')) {
      foreach ($division->get('surfaces') as $delta => $surface) {
        /** @var \Drupal\ps_division\Plugin\Field\FieldType\SurfaceItem $surface */
        $value = $surface->getValue();
        if ($value !== NULL && $value < 0) {
          $errors[] = "Surface #{$delta}: value cannot be negative.";
        }

        $unit = $surface->getUnit();
        if ($unit && !$this->dictionaryManager->isValid('surface_unit', $unit)) {
          $errors[] = "Surface #{$delta}: invalid unit '{$unit}'.";
        }

        $type = $surface->getType();
        if ($type && !$this->dictionaryManager->isValid('surface_type', $type)) {
          $errors[] = "Surface #{$delta}: invalid type '{$type}'.";
        }

        $nature = $surface->getNature();
        if ($nature && !$this->dictionaryManager->isValid('surface_nature', $nature)) {
          $errors[] = "Surface #{$delta}: invalid nature '{$nature}'.";
        }

        $qual = $surface->getQualification();
        if ($qual && !$this->dictionaryManager->isValid('surface_qualification', $qual)) {
          $errors[] = "Surface #{$delta}: invalid qualification '{$qual}'.";
        }
      }
    }

    // Validate entity-level type/nature dictionary codes.
    $entityTypeCode = $division->get('type')->value;
    if (is_string($entityTypeCode) && $entityTypeCode !== '' && !$this->dictionaryManager->isValid('surface_type', $entityTypeCode)) {
      $errors[] = "Division: invalid type '{$entityTypeCode}'.";
    }

    $entityNatureCode = $division->get('nature')->value;
    if (is_string($entityNatureCode) && $entityNatureCode !== '' && !$this->dictionaryManager->isValid('surface_nature', $entityNatureCode)) {
      $errors[] = "Division: invalid nature '{$entityNatureCode}'.";
    }

    return $errors;
  }

  /**
   * {@inheritdoc}
   */
  public function getSummary(DivisionInterface $division): array {
    return [
      'id' => $division->id(),
      'building_name' => $division->getBuildingName(),
      'type' => $division->get('type')->value,
      'nature' => $division->get('nature')->value,
      'lot' => $division->getLot(),
      'total_surface' => $division->getTotalSurface(),
    ];
  }

}
