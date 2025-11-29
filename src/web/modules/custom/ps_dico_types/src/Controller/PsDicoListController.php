<?php

namespace Drupal\ps_dico_types\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Url;
use Drupal\ps_dico_types\PsDicoTypeInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Controller for displaying dictionary items filtered by type.
 */
class PsDicoListController extends ControllerBase {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Constructs a PsDicoListController object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager) {
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager')
    );
  }

  /**
   * Displays dictionary items for a specific type.
   *
   * @param \Drupal\ps_dico_types\PsDicoTypeInterface $ps_dico_type
   *   The dictionary type entity.
   *
   * @return array
   *   A render array.
   */
  public function listing(PsDicoTypeInterface $ps_dico_type) {
    $storage = $this->entityTypeManager->getStorage('ps_dico');
    
    // Load all dictionary items for this type.
    $query = $storage->getQuery()
      ->condition('type', $ps_dico_type->id())
      ->accessCheck(TRUE);
    
    $ids = $query->execute();
    $entities = $storage->loadMultiple($ids);
    
    // Sort by weight, then label.
    uasort($entities, function ($a, $b) {
      $a_weight = $a->getWeight();
      $b_weight = $b->getWeight();
      
      if ($a_weight == $b_weight) {
        return strcasecmp($a->label(), $b->label());
      }
      
      return ($a_weight < $b_weight) ? -1 : 1;
    });

    // Build the table.
    $rows = [];
    foreach ($entities as $entity) {
      $row = [];
      $row[] = $entity->label();
      $row[] = $entity->id();
      $row[] = $entity->getWeight();
      
      // Operations.
      $operations = [
        'edit' => [
          'title' => $this->t('Edit'),
          'url' => $entity->toUrl('edit-form'),
        ],
        'delete' => [
          'title' => $this->t('Delete'),
          'url' => $entity->toUrl('delete-form'),
        ],
      ];
      
      $row[] = [
        'data' => [
          '#type' => 'operations',
          '#links' => $operations,
        ],
      ];
      
      $rows[] = $row;
    }

    $build['description'] = [
      '#markup' => '<p>' . $this->t('Dictionary items for type: <strong>@type</strong>', [
        '@type' => $ps_dico_type->label(),
      ]) . '</p>',
    ];

    if ($ps_dico_type->getDescription()) {
      $build['type_description'] = [
        '#markup' => '<p><em>' . $ps_dico_type->getDescription() . '</em></p>',
      ];
    }

    $build['table'] = [
      '#type' => 'table',
      '#header' => [
        $this->t('Label'),
        $this->t('Machine name'),
        $this->t('Weight'),
        $this->t('Operations'),
      ],
      '#rows' => $rows,
      '#empty' => $this->t('No dictionary items found for this type.'),
      '#cache' => [
        'tags' => ['ps_dico_type:' . $ps_dico_type->id()],
        'contexts' => ['languages'],
      ],
    ];

    // Add link to create new item.
    $build['add_link'] = [
      '#type' => 'link',
      '#title' => $this->t('Add dictionary item'),
      '#url' => Url::fromRoute('entity.ps_dico.add_form', [], [
        'query' => [
          'type' => $ps_dico_type->id(),
          'destination' => 'type_collection',
        ],
      ]),
      '#attributes' => ['class' => ['button', 'button--primary']],
      '#prefix' => '<div class="action-links">',
      '#suffix' => '</div>',
    ];

    return $build;
  }

  /**
   * The title callback for the listing page.
   *
   * @param \Drupal\ps_dico_types\PsDicoTypeInterface $ps_dico_type
   *   The dictionary type entity.
   *
   * @return string
   *   The page title.
   */
  public function title(PsDicoTypeInterface $ps_dico_type) {
    return $this->t('Dictionary items: @type', ['@type' => $ps_dico_type->label()]);
  }

}
