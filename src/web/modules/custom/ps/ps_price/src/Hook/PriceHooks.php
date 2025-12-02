<?php

declare(strict_types=1);

namespace Drupal\ps_price\Hook;

use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Hook\Attribute\Hook;
use Drupal\ps_price\Event\PriceFieldParentEntityEvent;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

/**
 * Hook implementations for ps_price module.
 *
 * Dispatches events when entities containing ps_price fields are modified.
 */
final class PriceHooks implements ContainerInjectionInterface {

  /**
   * Constructs a PriceHooks object.
   *
   * @param \Symfony\Contracts\EventDispatcher\EventDispatcherInterface $eventDispatcher
   *   The event dispatcher service.
   */
  public function __construct(
    private readonly EventDispatcherInterface $eventDispatcher,
  ) {}

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): self {
    return new self(
      $container->get('event_dispatcher'),
    );
  }

  /**
   * Implements hook_entity_presave().
   *
   * Dispatches event before entity with ps_price field is saved.
   */
  #[Hook('entity_presave')]
  public function entityPresave(EntityInterface $entity): void {
    $this->dispatchPriceFieldEvents($entity, 'presave');
  }

  /**
   * Implements hook_entity_insert().
   *
   * Dispatches event after entity with ps_price field is inserted.
   */
  #[Hook('entity_insert')]
  public function entityInsert(EntityInterface $entity): void {
    $this->dispatchPriceFieldEvents($entity, 'insert');
  }

  /**
   * Implements hook_entity_update().
   *
   * Dispatches event after entity with ps_price field is updated.
   */
  #[Hook('entity_update')]
  public function entityUpdate(EntityInterface $entity): void {
    $this->dispatchPriceFieldEvents($entity, 'update');
  }

  /**
   * Dispatches events for all ps_price fields in an entity.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity to check.
   * @param string $operation
   *   The operation type.
   */
  private function dispatchPriceFieldEvents(EntityInterface $entity, string $operation): void {
    // Only process fieldable entities.
    if (!$entity->getEntityType()->entityClassImplements('\Drupal\Core\Entity\FieldableEntityInterface')) {
      return;
    }

    /** @var \Drupal\Core\Entity\FieldableEntityInterface $entity */
    foreach ($entity->getFieldDefinitions() as $field_name => $field_definition) {
      // Check if field is ps_price type.
      if ($field_definition->getType() !== 'ps_price') {
        continue;
      }

      // Dispatch event for each delta.
      $items = $entity->get($field_name);
      foreach ($items as $delta => $item) {
        if (!$item->isEmpty()) {
          $event = new PriceFieldParentEntityEvent($entity, $field_name, $delta, $operation);
          $this->eventDispatcher->dispatch($event, PriceFieldParentEntityEvent::EVENT_NAME);
        }
      }
    }
  }

}
