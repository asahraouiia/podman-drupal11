<?php

namespace Drupal\ps_dico_types\Service;

use Drupal\Core\Config\ConfigFactoryInterface;

/**
 * Provides typed access to ps_dico_types module settings.
 *
 * This service centralizes all configuration access following PropertySearch
 * architecture guidelines. Always use this service instead of direct
 * \Drupal::config() calls.
 */
class SettingsManager {

  /**
   * The configuration object.
   *
   * @var \Drupal\Core\Config\ImmutableConfig
   */
  protected $config;

  /**
   * Constructs a SettingsManager object.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory service.
   */
  public function __construct(ConfigFactoryInterface $config_factory) {
    $this->config = $config_factory->get('ps_dico_types.settings');
  }

  /**
   * Gets a raw configuration value.
   *
   * @param string $key
   *   The configuration key.
   * @param mixed $default
   *   The default value if the key doesn't exist.
   *
   * @return mixed
   *   The configuration value.
   */
  public function get(string $key, $default = NULL) {
    $value = $this->config->get($key);
    return $value !== NULL ? $value : $default;
  }

  /**
   * Gets a boolean configuration value.
   *
   * @param string $key
   *   The configuration key.
   * @param bool $default
   *   The default value if the key doesn't exist.
   *
   * @return bool
   *   The configuration value as a boolean.
   */
  public function getBool(string $key, bool $default = FALSE): bool {
    $value = $this->get($key, $default);
    return (bool) $value;
  }

  /**
   * Gets an integer configuration value.
   *
   * @param string $key
   *   The configuration key.
   * @param int $default
   *   The default value if the key doesn't exist.
   *
   * @return int
   *   The configuration value as an integer.
   */
  public function getInt(string $key, int $default = 0): int {
    $value = $this->get($key, $default);
    return (int) $value;
  }

  /**
   * Gets an array configuration value.
   *
   * @param string $key
   *   The configuration key.
   * @param array $default
   *   The default value if the key doesn't exist.
   *
   * @return array
   *   The configuration value as an array.
   */
  public function getArray(string $key, array $default = []): array {
    $value = $this->get($key, $default);
    return is_array($value) ? $value : $default;
  }

  /**
   * Gets the cache TTL setting.
   *
   * @return int
   *   The cache time-to-live in seconds (default: 900 = 15 minutes).
   */
  public function getCacheTtl(): int {
    return $this->getInt('cache_ttl', 900);
  }

  /**
   * Checks if telemetry is enabled.
   *
   * @return bool
   *   TRUE if telemetry is enabled, FALSE otherwise.
   */
  public function isTelemetryEnabled(): bool {
    return $this->getBool('enable_telemetry', TRUE);
  }

  /**
   * Gets all settings as an array.
   *
   * @return array
   *   All configuration settings.
   */
  public function getAll(): array {
    return $this->config->getRawData();
  }

}
