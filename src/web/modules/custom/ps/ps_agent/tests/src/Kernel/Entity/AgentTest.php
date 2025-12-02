<?php

declare(strict_types=1);

namespace Drupal\Tests\ps_agent\Kernel\Entity;

use Drupal\KernelTests\KernelTestBase;
use Drupal\ps_agent\Entity\Agent;

/**
 * Tests for Agent entity.
 *
 * @coversDefaultClass \Drupal\ps_agent\Entity\Agent
 * @group ps_agent
 */
final class AgentTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'system',
    'user',
    'field',
    'text',
    'image',
    'file',
    'telephone',
    'ps',
    'ps_dictionary',
    'ps_agent',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->installEntitySchema('user');
    $this->installEntitySchema('agent');
    $this->installConfig(['ps', 'ps_agent']);
  }

  /**
   * Tests agent creation and basic getters.
   *
   * @covers ::create
   * @covers ::getExternalId
   * @covers ::getFirstName
   * @covers ::getLastName
   * @covers ::getEmail
   * @covers ::getPhone
   */
  public function testAgentCreation(): void {
    $agent = Agent::create([
      'external_id' => 'CRM-12345',
      'first_name' => 'John',
      'last_name' => 'Doe',
      'email' => 'john.doe@example.com',
      'phone' => '+33123456789',
    ]);

    $this->assertInstanceOf(Agent::class, $agent);
    $this->assertSame('CRM-12345', $agent->getExternalId());
    $this->assertSame('John', $agent->getFirstName());
    $this->assertSame('Doe', $agent->getLastName());
    $this->assertSame('john.doe@example.com', $agent->getEmail());
    $this->assertSame('+33123456789', $agent->getPhone());
  }

  /**
   * Tests entity label generation.
   *
   * @covers ::label
   */
  public function testLabelGeneration(): void {
    $agent = Agent::create([
      'external_id' => 'CRM-001',
      'first_name' => 'Jane',
      'last_name' => 'Smith',
      'email' => 'jane@example.com',
    ]);

    $this->assertSame('Smith Jane', $agent->label());
  }

  /**
   * Tests agent save and load.
   *
   * @covers ::save
   * @covers ::label
   */
  public function testAgentSaveAndLoad(): void {
    $agent = Agent::create([
      'external_id' => 'CRM-999',
      'first_name' => 'Test',
      'last_name' => 'Agent',
      'email' => 'test@example.com',
      'phone' => '+33987654321',
    ]);

    $agent->save();
    $this->assertNotEmpty($agent->id());

    $loadedAgent = Agent::load($agent->id());
    $this->assertInstanceOf(Agent::class, $loadedAgent);
    $this->assertSame('CRM-999', $loadedAgent->getExternalId());
    $this->assertSame('Agent Test', $loadedAgent->label());
    $this->assertSame('+33987654321', $loadedAgent->getPhone());
  }

  /**
   * Tests setters.
   *
   * @covers ::setExternalId
   */
  public function testSetters(): void {
    $agent = Agent::create([
      'first_name' => 'Alice',
      'last_name' => 'Brown',
      'email' => 'alice@example.com',
    ]);

    $agent->setExternalId('NEW-ID');
    $this->assertSame('NEW-ID', $agent->getExternalId());
  }

  /**
   * Tests timestamps.
   *
   * @covers ::getCreatedTime
   * @covers ::getChangedTime
   */
  public function testTimestamps(): void {
    $agent = Agent::create([
      'external_id' => 'CRM-TS',
      'first_name' => 'Time',
      'last_name' => 'Test',
      'email' => 'time@example.com',
    ]);

    $agent->save();

    $this->assertGreaterThan(0, $agent->getCreatedTime());
    $this->assertGreaterThan(0, $agent->getChangedTime());
  }

}
