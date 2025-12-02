<?php

declare(strict_types=1);

namespace Drupal\ps_price\Service;

use Drupal\Core\Entity\EntityInterface;
use Drupal\ps_price\Entity\PriceRuleInterface;

/**
 * Interface for price rule matching service.
 *
 * Determines which PriceRule entity applies to a given context based on
 * entity properties (property_type, transaction_type, etc.).
 *
 * @see \Drupal\ps_price\Service\PriceRuleMatcher
 * @see docs/modules/ps_price.md#price-rule-matcher
 */
interface PriceRuleMatcherInterface {

  /**
   * Finds the matching price rule for an entity.
   *
   * Matches based on:
   * - property_type (from entity field)
   * - transaction_type (from entity field)
   * - Optional: division, country, or other contextual fields
   *
   * Priority: Most specific rule wins (multiple matching criteria > fewer).
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity containing ps_price field (typically node_offer).
   *
   * @return \Drupal\ps_price\Entity\PriceRuleInterface|null
   *   The matching price rule, or NULL if no match found.
   *
   * @throws \InvalidArgumentException
   *   If entity does not have required fields for matching.
   */
  public function getMatchingRule(EntityInterface $entity): ?PriceRuleInterface;

  /**
   * Checks if a matching price rule exists for an entity.
   *
   * Lightweight alternative to getMatchingRule() when only existence check needed.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity to check.
   *
   * @return bool
   *   TRUE if a matching rule exists, FALSE otherwise.
   */
  public function hasMatchingRule(EntityInterface $entity): bool;

  /**
   * Gets all matching rules for an entity.
   *
   * Returns all PriceRule entities that match the given entity's properties,
   * ordered by specificity (most specific first).
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity to match against.
   *
   * @return \Drupal\ps_price\Entity\PriceRuleInterface[]
   *   Array of matching price rules, keyed by entity ID.
   */
  public function getAllMatchingRules(EntityInterface $entity): array;

}
