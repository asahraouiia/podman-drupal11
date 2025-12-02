<?php

declare(strict_types=1);

namespace Drupal\Tests\ps\Unit\Service;

use Drupal\Core\Database\Connection;
use Drupal\Core\Database\StatementInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\Logger\LoggerChannelInterface;
use Drupal\ps\Service\HealthCheckManager;
use Drupal\Tests\UnitTestCase;

/**
 * Tests HealthCheckManager service.
 *
 * @coversDefaultClass \Drupal\ps\Service\HealthCheckManager
 * @group ps
 */
class HealthCheckManagerTest extends UnitTestCase {

  /**
   * The database connection.
   *
   * @var \Drupal\Core\Database\Connection|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $database;

  /**
   * The logger factory.
   *
   * @var \Drupal\Core\Logger\LoggerChannelFactoryInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $loggerFactory;

  /**
   * The logger channel.
   *
   * @var \Drupal\Core\Logger\LoggerChannelInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $logger;

  /**
   * The health check manager.
   *
   * @var \Drupal\ps\Service\HealthCheckManager
   */
  protected $healthCheckManager;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->database = $this->createMock(Connection::class);
    $this->logger = $this->createMock(LoggerChannelInterface::class);
    $this->loggerFactory = $this->createMock(LoggerChannelFactoryInterface::class);

    $this->loggerFactory->method('get')
      ->with('ps')
      ->willReturn($this->logger);

    $this->healthCheckManager = new HealthCheckManager(
      $this->database,
      $this->loggerFactory
    );
  }

  /**
   * Tests performHealthCheck with healthy database.
   *
   * @covers ::performHealthCheck
   * @covers ::checkDatabase
   */
  public function testPerformHealthCheckHealthy(): void {
    $statement = $this->createMock(StatementInterface::class);
    $statement->method('fetchField')->willReturn('1');

    $this->database->method('query')
      ->with('SELECT 1')
      ->willReturn($statement);

    $result = $this->healthCheckManager->performHealthCheck();

    $this->assertIsArray($result);
    $this->assertArrayHasKey('status', $result);
    $this->assertSame('healthy', $result['status']);
    $this->assertArrayHasKey('checks', $result);
    $this->assertArrayHasKey('database', $result['checks']);
    $this->assertTrue($result['checks']['database']);
  }

  /**
   * Tests performHealthCheck with database failure.
   *
   * @covers ::performHealthCheck
   * @covers ::checkDatabase
   */
  public function testPerformHealthCheckUnhealthy(): void {
    $this->database->method('query')
      ->willThrowException(new \Exception('Database connection failed'));

    $result = $this->healthCheckManager->performHealthCheck();

    $this->assertIsArray($result);
    $this->assertArrayHasKey('status', $result);
    $this->assertSame('unhealthy', $result['status']);
    $this->assertArrayHasKey('checks', $result);
    $this->assertArrayHasKey('database', $result['checks']);
    $this->assertFalse($result['checks']['database']);
  }

  /**
   * Tests checkDatabase with successful connection.
   *
   * @covers ::checkDatabase
   */
  public function testCheckDatabaseSuccess(): void {
    $statement = $this->createMock(StatementInterface::class);
    $statement->method('fetchField')->willReturn('1');

    $this->database->method('query')
      ->with('SELECT 1')
      ->willReturn($statement);

    $result = $this->healthCheckManager->checkDatabase();
    $this->assertTrue($result);
  }

  /**
   * Tests checkDatabase with failed connection.
   *
   * @covers ::checkDatabase
   */
  public function testCheckDatabaseFailure(): void {
    $this->database->method('query')
      ->willThrowException(new \Exception('Connection error'));

    $result = $this->healthCheckManager->checkDatabase();
    $this->assertFalse($result);
  }

  /**
   * Tests getStatusSummary method.
   *
   * @covers ::getStatusSummary
   */
  public function testGetStatusSummary(): void {
    $statement = $this->createMock(StatementInterface::class);
    $statement->method('fetchField')->willReturn('1');

    $this->database->method('query')
      ->with('SELECT 1')
      ->willReturn($statement);

    $summary = $this->healthCheckManager->getStatusSummary();

    $this->assertIsArray($summary);
    $this->assertArrayHasKey('database', $summary);
    $this->assertSame('OK', $summary['database']);
    $this->assertArrayHasKey('last_check', $summary);
    $this->assertIsInt($summary['last_check']);
  }

}
