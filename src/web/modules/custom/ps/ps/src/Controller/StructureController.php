<?php

declare(strict_types=1);

namespace Drupal\ps\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Url;

/**
 * Structure overview controller.
 */
class StructureController extends ControllerBase {

  /**
   * Structure overview page.
   *
   * @return array
   *   Render array.
   */
  public function overview(): array {
    $items = [];

    // Dictionary Types.
    if ($this->moduleHandler()->moduleExists('ps_dictionary')) {
      $items[] = [
        '#type' => 'link',
        '#title' => $this->t('Dictionaries'),
        '#url' => Url::fromRoute('entity.ps_dictionary_type.collection'),
        '#prefix' => '<div class="admin-item">',
        '#suffix' => '<div class="description">' . $this->t('Manage business dictionary types and entries.') . '</div></div>',
      ];
    }

    // Features.
    if ($this->moduleHandler()->moduleExists('ps_features')) {
      $items[] = [
        '#type' => 'link',
        '#title' => $this->t('Features'),
        '#url' => Url::fromRoute('entity.ps_feature.collection'),
        '#prefix' => '<div class="admin-item">',
        '#suffix' => '<div class="description">' . $this->t('Manage property features catalog.') . '</div></div>',
      ];
    }

    return [
      '#theme' => 'item_list',
      '#title' => $this->t('Structure'),
      '#items' => $items,
      '#empty' => $this->t('No structure items available.'),
      '#attached' => [
        'library' => ['system/admin'],
      ],
    ];
  }

  /**
   * Configuration overview page.
   *
   * @return array
   *   Render array.
   */
  public function configOverview(): array {
    $items = [];

    // General settings.
    $items[] = [
      '#type' => 'link',
      '#title' => $this->t('General'),
      '#url' => Url::fromRoute('ps.admin_config_realestate'),
      '#prefix' => '<div class="admin-item">',
      '#suffix' => '<div class="description">' . $this->t('Global PropertySearch settings.') . '</div></div>',
    ];

    // Dictionary settings.
    if ($this->moduleHandler()->moduleExists('ps_dictionary')) {
      $items[] = [
        '#type' => 'link',
        '#title' => $this->t('Dictionary'),
        '#url' => Url::fromRoute('ps_dictionary.settings'),
        '#prefix' => '<div class="admin-item">',
        '#suffix' => '<div class="description">' . $this->t('Dictionary import and validation settings.') . '</div></div>',
      ];
    }

    // Features settings.
    if ($this->moduleHandler()->moduleExists('ps_features')) {
      $items[] = [
        '#type' => 'link',
        '#title' => $this->t('Feature'),
        '#url' => Url::fromRoute('ps_features.settings_form'),
        '#prefix' => '<div class="admin-item">',
        '#suffix' => '<div class="description">' . $this->t('Feature catalog settings.') . '</div></div>',
      ];
    }

    // Price settings.
    if ($this->moduleHandler()->moduleExists('ps_price')) {
      $items[] = [
        '#type' => 'link',
        '#title' => $this->t('Price'),
        '#url' => Url::fromRoute('ps_price.settings'),
        '#prefix' => '<div class="admin-item">',
        '#suffix' => '<div class="description">' . $this->t('Price display and currency settings.') . '</div></div>',
      ];
    }

    return [
      '#theme' => 'item_list',
      '#title' => $this->t('Configuration'),
      '#items' => $items,
      '#empty' => $this->t('No configuration items available.'),
      '#attached' => [
        'library' => ['system/admin'],
      ],
    ];
  }

}
