<?php

namespace Drupal\ps_dico_types\Plugin\Field\FieldWidget;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\WidgetBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Plugin implementation of the 'ps_dictionary_select' widget.
 *
 * @FieldWidget(
 *   id = "ps_dictionary_select",
 *   label = @Translation("Dictionary select"),
 *   field_types = {
 *     "ps_dictionary"
 *   }
 * )
 */
class PsDictionarySelectWidget extends WidgetBase implements ContainerFactoryPluginInterface {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Constructs a PsDictionarySelectWidget object.
   *
   * @param string $plugin_id
   *   The plugin ID.
   * @param mixed $plugin_definition
   *   The plugin definition.
   * @param \Drupal\Core\Field\FieldDefinitionInterface $field_definition
   *   The field definition.
   * @param array $settings
   *   The field settings.
   * @param array $third_party_settings
   *   Any third party settings.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   */
  public function __construct(
    $plugin_id,
    $plugin_definition,
    FieldDefinitionInterface $field_definition,
    array $settings,
    array $third_party_settings,
    EntityTypeManagerInterface $entity_type_manager
  ) {
    parent::__construct($plugin_id, $plugin_definition, $field_definition, $settings, $third_party_settings);
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $plugin_id,
      $plugin_definition,
      $configuration['field_definition'],
      $configuration['settings'],
      $configuration['third_party_settings'],
      $container->get('entity_type.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return [] + parent::defaultSettings();
  }



  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = [];
    
    // Read dictionary_type from field storage settings
    $type_id = $this->fieldDefinition->getFieldStorageDefinition()->getSetting('dictionary_type');
    if ($type_id) {
      $type = $this->entityTypeManager->getStorage('ps_dico_type')->load($type_id);
      $summary[] = $this->t('Dictionary type: @type', [
        '@type' => $type ? $type->label() : $this->t('Unknown'),
      ]);
    }
    else {
      $summary[] = $this->t('Dictionary type not configured');
    }

    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    // Read dictionary_type from field storage settings
    $type_id = $this->fieldDefinition->getFieldStorageDefinition()->getSetting('dictionary_type');
    
    if (!$type_id) {
      $element['value'] = [
        '#markup' => $this->t('Please configure the dictionary type in the field storage settings.'),
      ];
      return $element;
    }

    // Load dictionary items for the selected type.
    $storage = $this->entityTypeManager->getStorage('ps_dico');
    $query = $storage->getQuery()
      ->condition('type', $type_id)
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

    // Build options.
    $options = ['' => $this->t('- Select -')];
    foreach ($entities as $entity) {
      $options[$entity->id()] = $entity->label();
    }

    $element['value'] = [
      '#type' => 'select',
      '#title' => $element['#title'],
      '#options' => $options,
      '#default_value' => $items[$delta]->value ?? '',
      '#required' => $element['#required'],
      '#description' => $element['#description'],
    ];

    return $element;
  }

}
