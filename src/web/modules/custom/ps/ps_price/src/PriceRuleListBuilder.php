<?php

declare(strict_types=1);

namespace Drupal\ps_price;

use Drupal\Core\Config\Entity\ConfigEntityListBuilder;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\ps_dictionary\Service\DictionaryManagerInterface;
use Drupal\ps_price\Entity\PriceRuleInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a listing of Price rules.
 *
 * Displays all configured price rules with their transaction type, unit,
 * period, currency, and flags. Shows a "Locked" badge for system rules.
 *
 * @see \Drupal\ps_price\Entity\PriceRule
 * @see docs/modules/ps_price.md#price-rules
 */
final class PriceRuleListBuilder extends ConfigEntityListBuilder {

  /**
   * Constructs a new PriceRuleListBuilder.
   *
   * @param \Drupal\Core\Entity\EntityTypeInterface $entity_type
   *   The entity type definition.
   * @param \Drupal\Core\Entity\EntityStorageInterface $storage
   *   The entity storage class.
   * @param \Drupal\ps_dictionary\Service\DictionaryManagerInterface $dictionaryManager
   *   The dictionary manager service.
   */
  public function __construct(
    EntityTypeInterface $entity_type,
    EntityStorageInterface $storage,
    private readonly DictionaryManagerInterface $dictionaryManager,
  ) {
    parent::__construct($entity_type, $storage);
  }

  /**
   * {@inheritdoc}
   */
  public static function createInstance(ContainerInterface $container, EntityTypeInterface $entity_type): self {
    return new self(
      $entity_type,
      $container->get('entity_type.manager')->getStorage($entity_type->id()),
      $container->get('ps_dictionary.manager'),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function buildHeader(): array {
    $header['label'] = $this->t('Label');
    $header['transaction'] = $this->t('Transaction Type');
    $header['unit'] = $this->t('Unit');
    $header['period'] = $this->t('Period');
    $header['currency'] = $this->t('Currency');
    $header['flags'] = $this->t('Flags');
    $header['weight'] = $this->t('Weight');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity): array {
    if (!$entity instanceof PriceRuleInterface) {
      throw new \InvalidArgumentException(sprintf(
        'Entity must be a PriceRule, got %s.',
        $entity->getEntityTypeId()
      ));
    }

    $row['label'] = $entity->label();

    // Add locked badge if applicable.
    if ($entity->isLocked()) {
      $row['label'] .= ' <span style="background: #ffd700; color: #000; padding: 2px 6px; border-radius: 3px; font-size: 0.85em; font-weight: bold; margin-left: 8px;">Locked</span>';
    }

    // Transaction type with label from dictionary.
    $transaction_code = $entity->getTransactionType();
    $transaction_label = $this->dictionaryManager->getLabel('transaction_type', $transaction_code);
    $row['transaction'] = $transaction_label ?: $transaction_code;

    // Unit code with label.
    $unit_code = $entity->getUnitCode();
    $unit_label = $this->dictionaryManager->getLabel('price_unit', $unit_code);
    $row['unit'] = $unit_label ?: $unit_code;

    // Period code with label.
    $period_code = $entity->getPeriodCode();
    $period_label = $this->dictionaryManager->getLabel('price_period', $period_code);
    $row['period'] = $period_label ?: $period_code;

    // Currency code with label.
    $currency_code = $entity->getCurrencyCode();
    $currency_label = $this->dictionaryManager->getLabel('currency', $currency_code);
    $row['currency'] = $currency_label ?: $currency_code;

    // Display flags.
    $flags = [];
    if ($entity->isVatExcluded()) {
      $flags[] = $this->t('HT');
    }
    if ($entity->isChargesIncluded()) {
      $flags[] = $this->t('CC');
    }
    else {
      $flags[] = $this->t('HC');
    }
    $row['flags'] = implode(' + ', $flags);

    $row['weight'] = (string) $entity->getWeight();

    return $row + parent::buildRow($entity);
  }

  /**
   * {@inheritdoc}
   */
  public function getDefaultOperations(EntityInterface $entity): array {
    $operations = parent::getDefaultOperations($entity);

    // Remove delete operation for locked rules.
    if ($entity instanceof PriceRuleInterface && $entity->isLocked()) {
      unset($operations['delete']);
    }

    return $operations;
  }

}
