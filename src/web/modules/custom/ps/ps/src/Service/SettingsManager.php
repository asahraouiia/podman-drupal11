<?php

declare(strict_types=1);

namespace Drupal\ps\Service;

use Drupal\Core\Config\ConfigFactoryInterface;

/**
 * Settings manager for PropertySearch configuration.
 */
class SettingsManager implements SettingsManagerInterface {

  /**
   * Constructs a SettingsManager.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $configFactory
   *   Config factory service.
   */
  public function __construct(
    private readonly ConfigFactoryInterface $configFactory,
  ) {}

  /**
   * {@inheritdoc}
   */
  public function get(string $key, mixed $default = NULL): mixed {
    $config = $this->configFactory->get('ps.settings');

    // Support dot notation: 'performance.slow_request_threshold'.
    $keys = explode('.', $key);
    $value = $config->get(array_shift($keys));

    foreach ($keys as $subkey) {
      if (is_array($value) && isset($value[$subkey])) {
        $value = $value[$subkey];
      }
      else {
        return $default;
      }
    }

    return $value ?? $default;
  }

  /**
   * {@inheritdoc}
   */
  public function set(string $key, mixed $value): void {
    $config = $this->configFactory->getEditable('ps.settings');
    $config->set($key, $value);
    $config->save();
  }

  /**
   * {@inheritdoc}
   */
  public function isPerformanceMonitoringEnabled(): bool {
    return (bool) $this->get('performance.enable_monitoring', TRUE);
  }

  /**
   * {@inheritdoc}
   */
  public function getSlowRequestThreshold(): int {
    return (int) $this->get('performance.slow_request_threshold', 1000);
  }

}
