<?php

declare(strict_types=1);

namespace Drupal\ps\Service;

use Drupal\Component\Utility\EmailValidatorInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\Logger\LoggerChannelInterface;

/**
 * Notification manager for PropertySearch platform.
 */
class NotificationManager implements NotificationManagerInterface {

  /**
   * The logger channel.
   */
  private readonly LoggerChannelInterface $logger;

  /**
   * Constructs a NotificationManager.
   *
   * @param \Drupal\Component\Utility\EmailValidatorInterface $emailValidator
   *   Email validator service.
   * @param \Drupal\Core\Logger\LoggerChannelFactoryInterface $loggerFactory
   *   Logger factory service.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $configFactory
   *   Config factory service.
   */
  public function __construct(
    private readonly EmailValidatorInterface $emailValidator,
    LoggerChannelFactoryInterface $loggerFactory,
    private readonly ConfigFactoryInterface $configFactory,
  ) {
    $this->logger = $loggerFactory->get('ps');
  }

  /**
   * {@inheritdoc}
   */
  public function send(string $recipient, string $subject, string $message, array $options = []): bool {
    $channel = $options['channel'] ?? $this->getDefaultChannel();

    if ($channel === 'email' && !$this->emailValidator->isValid($recipient)) {
      $this->logger->error('Invalid email recipient: @recipient', [
        '@recipient' => $recipient,
      ]);
      return FALSE;
    }

    $this->logger->info('Notification sent to @recipient via @channel: @subject', [
      '@recipient' => $recipient,
      '@channel' => $channel,
      '@subject' => $subject,
    ]);

    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function getDefaultChannel(): string {
    $config = $this->configFactory->get('ps.settings');
    return (string) ($config->get('notification.default_channel') ?? 'email');
  }

  /**
   * {@inheritdoc}
   */
  public function getRetryAttempts(): int {
    $config = $this->configFactory->get('ps.settings');
    return (int) ($config->get('notification.retry_attempts') ?? 3);
  }

}
