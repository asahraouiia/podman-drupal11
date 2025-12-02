<?php

declare(strict_types=1);

namespace Drupal\Tests\ps\Unit\Service;

use Drupal\Component\Utility\EmailValidatorInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Config\ImmutableConfig;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\Logger\LoggerChannelInterface;
use Drupal\ps\Service\NotificationManager;
use Drupal\Tests\UnitTestCase;

/**
 * Tests NotificationManager service.
 *
 * @coversDefaultClass \Drupal\ps\Service\NotificationManager
 * @group ps
 */
class NotificationManagerTest extends UnitTestCase {

  /**
   * The email validator.
   *
   * @var \Drupal\Component\Utility\EmailValidatorInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $emailValidator;

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
   * The config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $configFactory;

  /**
   * The notification manager.
   *
   * @var \Drupal\ps\Service\NotificationManager
   */
  protected $notificationManager;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->emailValidator = $this->createMock(EmailValidatorInterface::class);
    $this->logger = $this->createMock(LoggerChannelInterface::class);
    $this->loggerFactory = $this->createMock(LoggerChannelFactoryInterface::class);
    $this->configFactory = $this->createMock(ConfigFactoryInterface::class);

    $this->loggerFactory->method('get')
      ->with('ps')
      ->willReturn($this->logger);

    $this->notificationManager = new NotificationManager(
      $this->emailValidator,
      $this->loggerFactory,
      $this->configFactory
    );
  }

  /**
   * Tests send method with valid email.
   *
   * @covers ::send
   */
  public function testSendWithValidEmail(): void {
    $this->emailValidator->method('isValid')
      ->with('test@example.com')
      ->willReturn(TRUE);

    $result = $this->notificationManager->send(
      'test@example.com',
      'Test Subject',
      'Test Message',
      ['channel' => 'email']
    );

    $this->assertTrue($result);
  }

  /**
   * Tests send method with invalid email.
   *
   * @covers ::send
   */
  public function testSendWithInvalidEmail(): void {
    $this->emailValidator->method('isValid')
      ->with('invalid-email')
      ->willReturn(FALSE);

    $result = $this->notificationManager->send(
      'invalid-email',
      'Test Subject',
      'Test Message',
      ['channel' => 'email']
    );

    $this->assertFalse($result);
  }

  /**
   * Tests send method with default channel.
   *
   * @covers ::send
   * @covers ::getDefaultChannel
   */
  public function testSendWithDefaultChannel(): void {
    $config = $this->createMock(ImmutableConfig::class);
    $config->method('get')
      ->with('notification.default_channel')
      ->willReturn('email');

    $this->configFactory->method('get')
      ->with('ps.settings')
      ->willReturn($config);

    $this->emailValidator->method('isValid')
      ->willReturn(TRUE);

    $result = $this->notificationManager->send(
      'test@example.com',
      'Test Subject',
      'Test Message'
    );

    $this->assertTrue($result);
  }

  /**
   * Tests getDefaultChannel method.
   *
   * @covers ::getDefaultChannel
   */
  public function testGetDefaultChannel(): void {
    $config = $this->createMock(ImmutableConfig::class);
    $config->method('get')
      ->with('notification.default_channel')
      ->willReturn('email');

    $this->configFactory->method('get')
      ->with('ps.settings')
      ->willReturn($config);

    $channel = $this->notificationManager->getDefaultChannel();
    $this->assertSame('email', $channel);
  }

  /**
   * Tests getDefaultChannel with missing config.
   *
   * @covers ::getDefaultChannel
   */
  public function testGetDefaultChannelWithMissingConfig(): void {
    $config = $this->createMock(ImmutableConfig::class);
    $config->method('get')
      ->with('notification.default_channel')
      ->willReturn(NULL);

    $this->configFactory->method('get')
      ->with('ps.settings')
      ->willReturn($config);

    $channel = $this->notificationManager->getDefaultChannel();
    $this->assertSame('email', $channel);
  }

  /**
   * Tests getRetryAttempts method.
   *
   * @covers ::getRetryAttempts
   */
  public function testGetRetryAttempts(): void {
    $config = $this->createMock(ImmutableConfig::class);
    $config->method('get')
      ->with('notification.retry_attempts')
      ->willReturn(5);

    $this->configFactory->method('get')
      ->with('ps.settings')
      ->willReturn($config);

    $attempts = $this->notificationManager->getRetryAttempts();
    $this->assertSame(5, $attempts);
  }

  /**
   * Tests getRetryAttempts with default value.
   *
   * @covers ::getRetryAttempts
   */
  public function testGetRetryAttemptsWithDefault(): void {
    $config = $this->createMock(ImmutableConfig::class);
    $config->method('get')
      ->with('notification.retry_attempts')
      ->willReturn(NULL);

    $this->configFactory->method('get')
      ->with('ps.settings')
      ->willReturn($config);

    $attempts = $this->notificationManager->getRetryAttempts();
    $this->assertSame(3, $attempts);
  }

}
