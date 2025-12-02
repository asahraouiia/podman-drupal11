<?php

declare(strict_types=1);

namespace Drupal\ps\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\ps\Plugin\DashboardWidgetManager;
use Drupal\ps\Service\SettingsManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Admin controller for PropertySearch.
 *
 * @see docs/specs/01-ps-socle.md#dashboard-plugins
 */
class AdminController extends ControllerBase {

  /**
   * Constructs an AdminController.
   *
   * @param \Drupal\ps\Service\SettingsManagerInterface $settingsManager
   *   Settings manager service.
   * @param \Drupal\ps\Plugin\DashboardWidgetManager $widgetManager
   *   Dashboard widget manager service.
   */
  public function __construct(
    private readonly SettingsManagerInterface $settingsManager,
    private readonly DashboardWidgetManager $widgetManager,
  ) {}

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): static {
    return new self(
      $container->get('ps.settings'),
      $container->get('ps.dashboard_widget.manager'),
    );
  }

  /**
   * Overview page with dashboard widgets.
   *
   * Displays all available dashboard widgets from PropertySearch modules
   * grouped by category.
   *
   * @return array
   *   Render array.
   *
   * @see docs/specs/01-ps-socle.md#32-dashboard--plugins-dashboardcard
   */
  public function overview(): array {
    $build = [
      '#type' => 'container',
      '#attributes' => ['class' => ['ps-dashboard']],
    ];

    // System information section.
    $build['system_info'] = [
      '#type' => 'details',
      '#title' => $this->t('System Information'),
      '#open' => TRUE,
      '#attributes' => ['class' => ['ps-dashboard-system-info']],
    ];

    $build['system_info']['items'] = [
      '#theme' => 'item_list',
      '#items' => [
        $this->t('Performance monitoring: @status', [
          '@status' => $this->settingsManager->isPerformanceMonitoringEnabled() ?
          $this->t('Enabled') : $this->t('Disabled'),
        ]),
        $this->t('Slow request threshold: @threshold ms', [
          '@threshold' => $this->settingsManager->getSlowRequestThreshold(),
        ]),
      ],
    ];

    // Dashboard widgets section.
    $build['widgets'] = [
      '#type' => 'container',
      '#attributes' => ['class' => ['ps-dashboard-widgets']],
    ];

    // Load and render all dashboard widgets.
    $definitions = $this->widgetManager->getDefinitions();
    $widgets_by_category = [];

    foreach ($definitions as $plugin_id => $definition) {
      try {
        /** @var \Drupal\ps\Plugin\DashboardWidgetInterface $widget */
        $widget = $this->widgetManager->createInstance($plugin_id);
        $category = $definition['category'] ?? 'general';
        $widgets_by_category[$category][$plugin_id] = $widget;
      }
      catch (\Exception $e) {
        $this->getLogger('ps')->error('Failed to create dashboard widget @id: @message', [
          '@id' => $plugin_id,
          '@message' => $e->getMessage(),
        ]);
      }
    }

    // Render widgets grouped by category.
    if (!empty($widgets_by_category)) {
      foreach ($widgets_by_category as $category => $widgets) {
        $category_key = 'category_' . $category;
        $build['widgets'][$category_key] = [
          '#type' => 'details',
          '#title' => $this->t('@category Widgets', ['@category' => ucfirst($category)]),
          '#open' => TRUE,
          '#attributes' => ['class' => ['ps-dashboard-category', 'ps-dashboard-category--' . $category]],
        ];

        foreach ($widgets as $plugin_id => $widget) {
          $widget_definition = $definitions[$plugin_id];
          $build['widgets'][$category_key][$plugin_id] = [
            '#type' => 'details',
            '#title' => $widget_definition['label'],
            '#description' => $widget_definition['description'] ?? NULL,
            '#open' => FALSE,
            '#attributes' => ['class' => ['ps-dashboard-widget']],
            'content' => $widget->build(),
          ];
        }
      }
    }
    else {
      $build['widgets']['empty'] = [
        '#markup' => '<p>' . $this->t('No dashboard widgets available. Install PropertySearch modules to see their widgets here.') . '</p>',
      ];
    }

    // Attach library if it exists.
    if (file_exists($this->moduleHandler()->getModule('ps')->getPath() . '/css/dashboard.css')) {
      $build['#attached']['library'][] = 'ps/dashboard';
    }

    return $build;
  }

}
