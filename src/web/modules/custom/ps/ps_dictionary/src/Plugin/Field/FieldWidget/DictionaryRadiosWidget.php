<?php

declare(strict_types=1);

namespace Drupal\ps_dictionary\Plugin\Field\FieldWidget;

use Drupal\Core\Field\Attribute\FieldWidget;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\Plugin\Field\FieldWidget\OptionsButtonsWidget;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\ps_dictionary\Service\DictionaryManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Plugin implementation of the 'ps_dictionary_radios' widget.
 *
 * Displays dictionary options as radio buttons. Best suited for
 * dictionaries with few options (≤5 items) like transaction_type,
 * offer_status, or visibility.
 *
 * @see \Drupal\ps_dictionary\Plugin\Field\FieldType\DictionaryItem
 * @see \Drupal\ps_dictionary\Service\DictionaryManagerInterface
 */
#[FieldWidget(
  id: "ps_dictionary_radios",
  label: new TranslatableMarkup("Radio buttons (dictionary)"),
  field_types: ["ps_dictionary"],
)]
class DictionaryRadiosWidget extends OptionsButtonsWidget implements ContainerFactoryPluginInterface {

  /**
   * Constructs a DictionaryRadiosWidget object.
   *
   * @param string $plugin_id
   *   The plugin_id for the widget.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Field\FieldDefinitionInterface $field_definition
   *   The definition of the field to which the widget is associated.
   * @param array $settings
   *   The widget settings.
   * @param array $third_party_settings
   *   Any third party settings.
   * @param \Drupal\ps_dictionary\Service\DictionaryManagerInterface $dictionaryManager
   *   The dictionary manager service.
   */
  public function __construct(
    $plugin_id,
    $plugin_definition,
    $field_definition,
    array $settings,
    array $third_party_settings,
    private readonly DictionaryManagerInterface $dictionaryManager,
  ) {
    parent::__construct($plugin_id, $plugin_definition, $field_definition, $settings, $third_party_settings);
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition): self {
    return new self(
      $plugin_id,
      $plugin_definition,
      $configuration['field_definition'],
      $configuration['settings'],
      $configuration['third_party_settings'] ?? [],
      $container->get('ps_dictionary.manager'),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state): array {
    $element = parent::formElement($items, $delta, $element, $form, $form_state);

    // Load options from dictionary.
    $dictionary_type = $this->getFieldSetting('dictionary_type');
    if ($dictionary_type) {
      $element['#options'] = $this->dictionaryManager->getOptions($dictionary_type);
    }
    else {
      $element['#options'] = [];
      $element['#description'] = $this->t('Dictionary type not configured for this field.');
    }

    return $element;
  }

}
