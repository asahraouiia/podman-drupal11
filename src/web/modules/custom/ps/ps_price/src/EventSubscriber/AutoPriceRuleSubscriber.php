<?php

declare(strict_types=1);

namespace Drupal\ps_price\EventSubscriber;

use Drupal\ps_price\Event\PriceFieldParentEntityEvent;
use Drupal\ps_price\Service\PriceRuleMatcherInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Subscriber for automatic price rule application.
 *
 * Listens to PriceFieldParentEntityEvent and automatically applies
 * unit_code and period_code from matching PriceRule entities based on
 * the parent entity's properties (property type, transaction type, etc.).
 *
 * Example: When saving an offer with property_type=office and
 * transaction_type=rent, this will auto-apply the matching rule's
 * unit (M2_YEAR) and period (YEAR) if no manual values are set.
 *
 * Priority: Manual values > Auto rules > Empty
 * - If unit_code or period_code are already set → preserve them (import XML)
 * - If both empty and rule matches → apply rule defaults
 * - If no rule matches → leave empty
 *
 * @see \Drupal\ps_price\Entity\PriceRule
 * @see \Drupal\ps_price\Service\PriceRuleMatcher
 * @see docs/modules/ps_price.md#auto-rules
 */
final class AutoPriceRuleSubscriber implements EventSubscriberInterface {

  /**
   * Constructs an AutoPriceRuleSubscriber object.
   *
   * @param \Drupal\ps_price\Service\PriceRuleMatcherInterface $priceRuleMatcher
   *   The price rule matcher service.
   */
  public function __construct(
    private readonly PriceRuleMatcherInterface $priceRuleMatcher,
  ) {}

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents(): array {
    return [
      PriceFieldParentEntityEvent::EVENT_NAME => ['onParentEntityChanged', 0],
    ];
  }

  /**
   * Reacts to parent entity changes.
   *
   * Applies price rule defaults for unit_code and period_code when they are
   * empty. Preserves manually set or imported values.
   *
   * @param \Drupal\ps_price\Event\PriceFieldParentEntityEvent $event
   *   The event object.
   */
  public function onParentEntityChanged(PriceFieldParentEntityEvent $event): void {
    // Only act on presave to allow modification before storage.
    if ($event->getOperation() !== 'presave') {
      return;
    }

    $entity = $event->getEntity();
    $field_name = $event->getFieldName();
    $delta = $event->getDelta();

    // Get the price field item.
    $price_item = $entity->get($field_name)->get($delta);
    if ($price_item === NULL) {
      return;
    }

    // Skip if unit/period are already manually set (import XML preservation).
    if (!empty($price_item->unit_code) || !empty($price_item->period_code)) {
      return;
    }

    // Find matching price rule for this entity.
    $rule = $this->priceRuleMatcher->getMatchingRule($entity);
    if ($rule === NULL) {
      return;
    }

    // Apply rule defaults for unit and period.
    if ($rule->getUnitCode() !== NULL) {
      $price_item->unit_code = $rule->getUnitCode();
    }
    if ($rule->getPeriodCode() !== NULL) {
      $price_item->period_code = $rule->getPeriodCode();
    }
  }

}
