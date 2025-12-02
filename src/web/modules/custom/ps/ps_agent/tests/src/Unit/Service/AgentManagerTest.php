<?php

declare(strict_types=1);

namespace Drupal\Tests\ps_agent\Unit\Service;

use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\Query\QueryInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\Logger\LoggerChannelInterface;
use Drupal\ps\Service\SettingsManagerInterface;
use Drupal\ps_agent\Entity\AgentInterface;
use Drupal\ps_agent\Service\AgentManager;
use Drupal\Tests\UnitTestCase;

/**
 * Tests for AgentManager service.
 *
 * @coversDefaultClass \Drupal\ps_agent\Service\AgentManager
 * @group ps_agent
 */
final class AgentManagerTest extends UnitTestCase {

  /**
   * The mocked entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  private EntityTypeManagerInterface $entityTypeManager;

  /**
   * The mocked settings manager.
   *
   * @var \Drupal\ps\Service\SettingsManagerInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  private SettingsManagerInterface $settingsManager;

  /**
   * The mocked logger factory.
   *
   * @var \Drupal\Core\Logger\LoggerChannelFactoryInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  private LoggerChannelFactoryInterface $loggerFactory;

  /**
   * The agent manager service under test.
   *
   * @var \Drupal\ps_agent\Service\AgentManager
   */
  private AgentManager $agentManager;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->entityTypeManager = $this->createMock(EntityTypeManagerInterface::class);
    $this->settingsManager = $this->createMock(SettingsManagerInterface::class);
    $this->loggerFactory = $this->createMock(LoggerChannelFactoryInterface::class);

    $logger = $this->createMock(LoggerChannelInterface::class);
    $this->loggerFactory->method('get')->willReturn($logger);

    $this->agentManager = new AgentManager(
      $this->entityTypeManager,
      $this->settingsManager,
      $this->loggerFactory,
    );
  }

  /**
   * Tests getActiveAgents() method.
   *
   * @covers ::getActiveAgents
   */
  public function testGetActiveAgents(): void {
    $storage = $this->createMock(EntityStorageInterface::class);
    $query = $this->createMock(QueryInterface::class);

    $this->entityTypeManager
      ->method('getStorage')
      ->with('agent')
      ->willReturn($storage);

    $storage->method('getQuery')->willReturn($query);

    $query->method('condition')->willReturnSelf();
    $query->method('accessCheck')->willReturnSelf();
    $query->method('sort')->willReturnSelf();
    $query->method('execute')->willReturn([1, 2]);

    $agent1 = $this->createMock(AgentInterface::class);
    $agent2 = $this->createMock(AgentInterface::class);

    $storage->method('loadMultiple')
      ->with([1, 2])
      ->willReturn([1 => $agent1, 2 => $agent2]);

    $result = $this->agentManager->getActiveAgents();

    $this->assertIsArray($result);
    $this->assertCount(2, $result);
    $this->assertSame($agent1, $result[1]);
    $this->assertSame($agent2, $result[2]);
  }

  /**
   * Tests getAgentByExternalId() with existing agent.
   *
   * @covers ::getAgentByExternalId
   */
  public function testGetAgentByExternalIdFound(): void {
    $storage = $this->createMock(EntityStorageInterface::class);
    $query = $this->createMock(QueryInterface::class);

    $this->entityTypeManager
      ->method('getStorage')
      ->with('agent')
      ->willReturn($storage);

    $storage->method('getQuery')->willReturn($query);

    $query->method('condition')->willReturnSelf();
    $query->method('accessCheck')->willReturnSelf();
    $query->method('range')->willReturnSelf();
    $query->method('execute')->willReturn([123]);

    $agent = $this->createMock(AgentInterface::class);
    $storage->method('load')->with(123)->willReturn($agent);

    $result = $this->agentManager->getAgentByExternalId('CRM-123');

    $this->assertSame($agent, $result);
  }

  /**
   * Tests getAgentByExternalId() with non-existing agent.
   *
   * @covers ::getAgentByExternalId
   */
  public function testGetAgentByExternalIdNotFound(): void {
    $storage = $this->createMock(EntityStorageInterface::class);
    $query = $this->createMock(QueryInterface::class);

    $this->entityTypeManager
      ->method('getStorage')
      ->with('agent')
      ->willReturn($storage);

    $storage->method('getQuery')->willReturn($query);

    $query->method('condition')->willReturnSelf();
    $query->method('accessCheck')->willReturnSelf();
    $query->method('range')->willReturnSelf();
    $query->method('execute')->willReturn([]);

    $result = $this->agentManager->getAgentByExternalId('CRM-999');

    $this->assertNull($result);
  }

  /**
   * Tests agentExists() method.
   *
   * @covers ::agentExists
   */
  public function testAgentExists(): void {
    $storage = $this->createMock(EntityStorageInterface::class);
    $query = $this->createMock(QueryInterface::class);

    $this->entityTypeManager
      ->method('getStorage')
      ->with('agent')
      ->willReturn($storage);

    $storage->method('getQuery')->willReturn($query);

    $query->method('condition')->willReturnSelf();
    $query->method('accessCheck')->willReturnSelf();
    $query->method('range')->willReturnSelf();

    // Test exists.
    $query->method('execute')->willReturn([1]);
    $agent = $this->createMock(AgentInterface::class);
    $storage->method('load')->willReturn($agent);

    $this->assertTrue($this->agentManager->agentExists('CRM-123'));
  }

  /**
   * Tests getFormattedName() method.
   *
   * @covers ::getFormattedName
   */
  public function testGetFormattedName(): void {
    $agent = $this->createMock(AgentInterface::class);
    $agent->method('label')->willReturn('Doe John');

    $result = $this->agentManager->getFormattedName($agent);

    $this->assertSame('Doe John', $result);
  }

  /**
   * Tests getBoEditableFields() method.
   *
   * @covers ::getBoEditableFields
   */
  public function testGetBoEditableFields(): void {
    $this->settingsManager
      ->method('get')
      ->with('ps_agent.settings')
      ->willReturn([
        'bo_editable_fields' => ['email', 'phone', 'internal_notes'],
      ]);

    $result = $this->agentManager->getBoEditableFields();

    $this->assertIsArray($result);
    $this->assertContains('email', $result);
    $this->assertContains('phone', $result);
    $this->assertContains('internal_notes', $result);
  }

  /**
   * Tests isBoEditableField() method.
   *
   * @covers ::isBoEditableField
   */
  public function testIsBoEditableField(): void {
    $this->settingsManager
      ->method('get')
      ->with('ps_agent.settings')
      ->willReturn([
        'bo_editable_fields' => ['email', 'phone'],
      ]);

    $this->assertTrue($this->agentManager->isBoEditableField('email'));
    $this->assertTrue($this->agentManager->isBoEditableField('phone'));
    $this->assertFalse($this->agentManager->isBoEditableField('first_name'));
  }

}
