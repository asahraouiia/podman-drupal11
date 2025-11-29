<?php

namespace Drupal\ps_dico_types\Plugin\Field\FieldFormatter;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\ps_dico_types\Service\SettingsManager;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Plugin implementation of the 'ps_dictionary_label' formatter.
 *
 * @FieldFormatter(
 *   id = "ps_dictionary_label",
 *   label = @Translation("Dictionary label"),
 *   field_types = {
 *     "ps_dictionary"
 *   }
 * )
 */
class PsDictionaryLabelFormatter extends FormatterBase implements ContainerFactoryPluginInterface {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The settings manager.
   *
   * @var \Drupal\ps_dico_types\Service\SettingsManager
   */
  protected $settingsManager;

  /**
   * Constructs a PsDictionaryLabelFormatter object.
   *
   * @param string $plugin_id
   *   The plugin ID.
   * @param mixed $plugin_definition
   *   The plugin definition.
   * @param \Drupal\Core\Field\FieldDefinitionInterface $field_definition
   *   The field definition.
   * @param array $settings
   *   The formatter settings.
   * @param string $label
   *   The label.
   * @param string $view_mode
   *   The view mode.
   * @param array $third_party_settings
   *   Any third party settings.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\ps_dico_types\Service\SettingsManager $settings_manager
   *   The settings manager.
   */
  public function __construct(
    $plugin_id,
    $plugin_definition,
    FieldDefinitionInterface $field_definition,
    array $settings,
    $label,
    $view_mode,
    array $third_party_settings,
    EntityTypeManagerInterface $entity_type_manager,
    SettingsManager $settings_manager
  ) {
    parent::__construct($plugin_id, $plugin_definition, $field_definition, $settings, $label, $view_mode, $third_party_settings);
    $this->entityTypeManager = $entity_type_manager;
    $this->settingsManager = $settings_manager;
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
      $configuration['label'],
      $configuration['view_mode'],
      $configuration['third_party_settings'],
      $container->get('entity_type.manager'),
      $container->get('ps_dico_types.settings_manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return [
      'link_to_entity' => FALSE,
    ] + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $elements = parent::settingsForm($form, $form_state);

    $elements['link_to_entity'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Link to dictionary item'),
      '#default_value' => $this->getSetting('link_to_entity'),
      '#description' => $this->t('If enabled, the dictionary label will be linked to the item edit page.'),
    ];

    return $elements;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = [];
    
    if ($this->getSetting('link_to_entity')) {
      $summary[] = $this->t('Link to dictionary item: Yes');
    }
    else {
      $summary[] = $this->t('Link to dictionary item: No');
    }

    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $elements = [];
    $link_to_entity = $this->getSetting('link_to_entity');

    foreach ($items as $delta => $item) {
      $value = $item->value;
      
      if (empty($value)) {
        continue;
      }

      $entity = $this->entityTypeManager->getStorage('ps_dico')->load($value);
      
      if (!$entity) {
        continue;
      }

      // Build cache metadata.
      $cache_tags = ['ps_dico:' . $entity->id()];
      if ($entity->getType()) {
        $cache_tags[] = 'ps_dico_type:' . $entity->getType();
      }

      if ($link_to_entity) {
        $elements[$delta] = [
          '#type' => 'link',
          '#title' => $entity->label(),
          '#url' => $entity->toUrl('edit-form'),
          '#cache' => [
            'tags' => $cache_tags,
            'contexts' => ['languages'],
            'max-age' => $this->settingsManager->getCacheTtl(),
          ],
        ];
      }
      else {
        $elements[$delta] = [
          '#markup' => $entity->label(),
          '#cache' => [
            'tags' => $cache_tags,
            'contexts' => ['languages'],
            'max-age' => $this->settingsManager->getCacheTtl(),
          ],
        ];
      }
    }

    return $elements;
  }

}
