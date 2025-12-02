<?php

declare(strict_types=1);

namespace Drupal\ps_dictionary\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\Attribute\FieldFormatter;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\ps_dictionary\Service\DictionaryManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Plugin implementation of the 'ps_dictionary_default' formatter.
 *
 * Displays dictionary code as human-readable label by resolving through
 * DictionaryManager. Falls back to code if label not found.
 *
 * Example: "VEN" → "Vente" (for transaction_type dictionary).
 *
 * @see \Drupal\ps_dictionary\Plugin\Field\FieldType\DictionaryItem
 * @see \Drupal\ps_dictionary\Service\DictionaryManagerInterface
 */
#[FieldFormatter(
  id: "ps_dictionary_default",
  label: new TranslatableMarkup("Dictionary label"),
  field_types: ["ps_dictionary"],
)]
class DictionaryDefaultFormatter extends FormatterBase implements ContainerFactoryPluginInterface {

  /**
   * Constructs a DictionaryDefaultFormatter object.
   *
   * @param string $plugin_id
   *   The plugin_id for the formatter.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Field\FieldDefinitionInterface $field_definition
   *   The definition of the field to which the formatter is associated.
   * @param array $settings
   *   The formatter settings.
   * @param string $label
   *   The formatter label display setting.
   * @param string $view_mode
   *   The view mode.
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
    $label,
    $view_mode,
    array $third_party_settings,
    private readonly DictionaryManagerInterface $dictionaryManager,
  ) {
    parent::__construct($plugin_id, $plugin_definition, $field_definition, $settings, $label, $view_mode, $third_party_settings);
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
      $configuration['label'],
      $configuration['view_mode'],
      $configuration['third_party_settings'] ?? [],
      $container->get('ps_dictionary.manager'),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode): array {
    $elements = [];
    $dictionary_type = $this->getFieldSetting('dictionary_type');

    if (!$dictionary_type) {
      return $elements;
    }

    foreach ($items as $delta => $item) {
      $code = $item->value;
      if ($code === NULL || $code === '') {
        continue;
      }

      // Resolve code to label via DictionaryManager.
      $label = $this->dictionaryManager->getLabel($dictionary_type, $code);

      $elements[$delta] = [
        '#markup' => $label ?? $code,
      ];
    }

    return $elements;
  }

}
