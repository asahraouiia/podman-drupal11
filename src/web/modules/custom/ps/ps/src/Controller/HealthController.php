<?php

declare(strict_types=1);

namespace Drupal\ps\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\ps\Service\HealthCheckManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;

/**
 * Health check controller for PropertySearch.
 */
class HealthController extends ControllerBase {

  /**
   * Constructs a HealthController.
   *
   * @param \Drupal\ps\Service\HealthCheckManagerInterface $healthCheckManager
   *   Health check manager service.
   */
  public function __construct(
    private readonly HealthCheckManagerInterface $healthCheckManager,
  ) {}

  /**
   * {@inheritdoc}
   *
   * @return static
   */
  public static function create(ContainerInterface $container): static {
    return new self(
      $container->get('ps.health_check'),
    );
  }

  /**
   * Perform health check.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *   JSON response with health check results.
   */
  public function check(): JsonResponse {
    $result = $this->healthCheckManager->performHealthCheck();
    $status = $result['status'] === 'healthy' ? 200 : 503;

    return new JsonResponse($result, $status);
  }

}
