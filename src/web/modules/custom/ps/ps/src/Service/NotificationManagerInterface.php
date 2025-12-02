<?php

declare(strict_types=1);

namespace Drupal\ps\Service;

/**
 * Interface for NotificationManager service.
 */
interface NotificationManagerInterface {

  /**
   * Send a notification.
   *
   * @param string $recipient
   *   Notification recipient (email, user ID, etc.).
   * @param string $subject
   *   Notification subject.
   * @param string $message
   *   Notification message.
   * @param array<string, mixed> $options
   *   Additional options.
   *
   * @return bool
   *   TRUE if notification was sent successfully.
   */
  public function send(string $recipient, string $subject, string $message, array $options = []): bool;

  /**
   * Get default notification channel.
   *
   * @return string
   *   Channel name (email, sms, etc.).
   */
  public function getDefaultChannel(): string;

  /**
   * Get retry attempts configuration.
   *
   * @return int
   *   Number of retry attempts.
   */
  public function getRetryAttempts(): int;

}
