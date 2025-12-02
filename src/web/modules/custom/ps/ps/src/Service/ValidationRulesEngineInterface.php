<?php

declare(strict_types=1);

namespace Drupal\ps\Service;

/**
 * Interface for ValidationRulesEngine service.
 */
interface ValidationRulesEngineInterface {

  /**
   * Validate data against defined rules.
   *
   * @param array<string, mixed> $data
   *   Data to validate.
   * @param array<string, mixed> $rules
   *   Validation rules to apply.
   *
   * @return array<string, mixed>
   *   Validation result with errors if any.
   */
  public function validate(array $data, array $rules): array;

  /**
   * Add a custom validation rule.
   *
   * @param string $name
   *   Rule name.
   * @param callable $callback
   *   Validation callback.
   */
  public function addRule(string $name, callable $callback): void;

  /**
   * Check if strict mode is enabled.
   *
   * @return bool
   *   TRUE if strict mode is enabled.
   */
  public function isStrictMode(): bool;

}
