<?php

declare(strict_types=1);

namespace Drupal\ps_price\Service;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\ps_price\Entity\PriceRuleInterface;

/**
 * Price rule matcher service.
 *
 * Determines which PriceRule applies to an entity based on transaction_type
 * and optional additional context fields (property_type, division, etc.).
 *
 * @see \Drupal\ps_price\Service\PriceRuleMatcherInterface
 * @see docs/modules/ps_price.md#price-rule-matcher
 */
final class PriceRuleMatcher implements PriceRuleMatcherInterface {

  /**
   * Constructs a PriceRuleMatcher object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The entity type manager service.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $configFactory
   *   The config factory service.
   */
  public function __construct(
    private readonly EntityTypeManagerInterface $entityTypeManager,
    private readonly ConfigFactoryInterface $configFactory,
  ) {}

  /**
   * {@inheritdoc}
   */
  public function getMatchingRule(EntityInterface $entity): ?PriceRuleInterface {
    $rules = $this->getAllMatchingRules($entity);
    return !empty($rules) ? reset($rules) : NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function hasMatchingRule(EntityInterface $entity): bool {
    return $this->getMatchingRule($entity) !== NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function getAllMatchingRules(EntityInterface $entity): array {
    // Extract transaction_type from entity.
    $transaction_type = $this->extractTransactionType($entity);
    if ($transaction_type === NULL) {
      return [];
    }

    // Load all price rules.
    $storage = $this->entityTypeManager->getStorage('ps_price_rule');
    $rules = $storage->loadMultiple();

    // Filter rules matching transaction_type.
    $matching_rules = [];
    foreach ($rules as $rule) {
      assert($rule instanceof PriceRuleInterface);
      if ($rule->getTransactionType() === $transaction_type) {
        $matching_rules[$rule->id()] = $rule;
      }
    }

    // Sort by weight (lower weight = higher priority).
    uasort($matching_rules, static fn($a, $b) => $a->getWeight() <=> $b->getWeight());

    return $matching_rules;
  }

  /**
   * Extracts transaction_type from entity.
   *
   * Iterates through configured field candidates in priority order.
   * Returns the first non-empty value found.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity to extract from.
   *
   * @return string|null
   *   The transaction type code, or NULL if not found.
   */
  private function extractTransactionType(EntityInterface $entity): ?string {
    $config = $this->configFactory->get('ps_price.settings');
    $candidates = $config->get('transaction_field_candidates') ?? [
      'field_transaction_type',
      'field_operation_code',
    ];

    foreach ($candidates as $field_name) {
      if ($entity->hasField($field_name) && !$entity->get($field_name)->isEmpty()) {
        return (string) $entity->get($field_name)->value;
      }
    }

    return NULL;
  }

}
