<?php

declare(strict_types=1);

namespace Drupal\ps\Service;

use Drupal\Core\Database\Connection;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\Logger\LoggerChannelInterface;

/**
 * Health check manager for PropertySearch platform.
 */
class HealthCheckManager implements HealthCheckManagerInterface {

  /**
   * The logger channel.
   */
  private readonly LoggerChannelInterface $logger;

  /**
   * Constructs a HealthCheckManager.
   *
   * @param \Drupal\Core\Database\Connection $database
   *   Database connection.
   * @param \Drupal\Core\Logger\LoggerChannelFactoryInterface $loggerFactory
   *   Logger factory service.
   */
  public function __construct(
    private readonly Connection $database,
    LoggerChannelFactoryInterface $loggerFactory,
  ) {
    $this->logger = $loggerFactory->get('ps');
  }

  /**
   * {@inheritdoc}
   */
  public function performHealthCheck(): array {
    $checks = [
      'database' => $this->checkDatabase(),
      'timestamp' => time(),
    ];

    $status = array_reduce($checks, function ($carry, $item) {
      return $carry && (is_bool($item) ? $item : TRUE);
    }, TRUE);

    $result = [
      'status' => $status ? 'healthy' : 'unhealthy',
      'checks' => $checks,
    ];

    $this->logger->info('Health check performed: @status', [
      '@status' => $result['status'],
    ]);

    return $result;
  }

  /**
   * {@inheritdoc}
   */
  public function checkDatabase(): bool {
    try {
      $this->database->query('SELECT 1')->fetchField();
      return TRUE;
    }
    catch (\Exception $e) {
      $this->logger->error('Database health check failed: @message', [
        '@message' => $e->getMessage(),
      ]);
      return FALSE;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getStatusSummary(): array {
    return [
      'database' => $this->checkDatabase() ? 'OK' : 'ERROR',
      'last_check' => time(),
    ];
  }

}
