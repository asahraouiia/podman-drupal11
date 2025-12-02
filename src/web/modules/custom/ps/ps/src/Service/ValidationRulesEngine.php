<?php

declare(strict_types=1);

namespace Drupal\ps\Service;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\Logger\LoggerChannelInterface;

/**
 * Validation rules engine for PropertySearch.
 */
class ValidationRulesEngine implements ValidationRulesEngineInterface {

  /**
   * The logger channel.
   */
  private readonly LoggerChannelInterface $logger;

  /**
   * Custom validation rules.
   *
   * @var array<string, callable>
   */
  private array $customRules = [];

  /**
   * Constructs a ValidationRulesEngine.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $configFactory
   *   Config factory service.
   * @param \Drupal\Core\Logger\LoggerChannelFactoryInterface $loggerFactory
   *   Logger factory service.
   */
  public function __construct(
    private readonly ConfigFactoryInterface $configFactory,
    LoggerChannelFactoryInterface $loggerFactory,
  ) {
    $this->logger = $loggerFactory->get('ps');
  }

  /**
   * {@inheritdoc}
   */
  public function validate(array $data, array $rules): array {
    $errors = [];

    foreach ($rules as $field => $rule) {
      $value = $data[$field] ?? NULL;

      if (isset($rule['required']) && $rule['required'] && empty($value)) {
        $errors[$field][] = "Field {$field} is required.";
        continue;
      }

      if (isset($rule['type']) && !$this->validateType($value, $rule['type'])) {
        $errors[$field][] = "Field {$field} must be of type {$rule['type']}.";
      }

      if (isset($rule['custom']) && is_string($rule['custom'])) {
        $customRule = $this->customRules[$rule['custom']] ?? NULL;
        if ($customRule && !$customRule($value)) {
          $errors[$field][] = "Field {$field} failed custom validation.";
        }
      }
    }

    $result = [
      'valid' => empty($errors),
      'errors' => $errors,
    ];

    if (!$result['valid']) {
      $this->logger->warning('Validation failed for data: @errors', [
        '@errors' => json_encode($errors),
      ]);
    }

    return $result;
  }

  /**
   * {@inheritdoc}
   */
  public function addRule(string $name, callable $callback): void {
    $this->customRules[$name] = $callback;
    $this->logger->debug('Added custom validation rule: @name', [
      '@name' => $name,
    ]);
  }

  /**
   * {@inheritdoc}
   */
  public function isStrictMode(): bool {
    $config = $this->configFactory->get('ps.settings');
    return (bool) $config->get('validation.strict_mode');
  }

  /**
   * Validate value type.
   *
   * @param mixed $value
   *   Value to validate.
   * @param string $type
   *   Expected type.
   *
   * @return bool
   *   TRUE if type matches.
   */
  private function validateType(mixed $value, string $type): bool {
    return match ($type) {
      'string' => is_string($value),
      'int', 'integer' => is_int($value),
      'float', 'double' => is_float($value),
      'bool', 'boolean' => is_bool($value),
      'array' => is_array($value),
      default => TRUE,
    };
  }

}
