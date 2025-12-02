<?php

declare(strict_types=1);

namespace Drupal\Tests\ps_price\Unit\Service;

use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\ps_price\Entity\PriceRuleInterface;
use Drupal\ps_price\Service\PriceRuleMatcher;
use Drupal\Tests\UnitTestCase;

/**
 * Unit tests for PriceRuleMatcher service.
 *
 * @coversDefaultClass \Drupal\ps_price\Service\PriceRuleMatcher
 * @group ps_price
 */
class PriceRuleMatcherTest extends UnitTestCase {

  /**
   * The service under test.
   */
  protected PriceRuleMatcher $matcher;

  /**
   * Mock entity type manager.
   */
  protected EntityTypeManagerInterface $entityTypeManager;

  /**
   * Mock storage.
   */
  protected EntityStorageInterface $storage;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->storage = $this->createMock(EntityStorageInterface::class);
    $this->entityTypeManager = $this->createMock(EntityTypeManagerInterface::class);
    $this->entityTypeManager->method('getStorage')
      ->with('ps_price_rule')
      ->willReturn($this->storage);

    $configFactory = $this->createMock(\Drupal\Core\Config\ConfigFactoryInterface::class);
    $config = $this->createMock(\Drupal\Core\Config\ImmutableConfig::class);
    $config->method('get')->with('transaction_field_candidates')
      ->willReturn(['field_transaction_type']);
    $configFactory->method('get')->with('ps_price.settings')->willReturn($config);

    $this->matcher = new PriceRuleMatcher($this->entityTypeManager, $configFactory);
  }

  /**
   * @covers ::getMatchingRule
   */
  public function testGetMatchingRuleWithNoTransactionType(): void {
    $entity = $this->createMock(\Drupal\Core\Entity\ContentEntityInterface::class);
    $entity->method('hasField')->willReturn(FALSE);

    $result = $this->matcher->getMatchingRule($entity);

    $this->assertNull($result);
  }

  /**
   * @covers ::getMatchingRule
   */
  public function testGetMatchingRuleWithEmptyTransactionType(): void {
    $field = $this->createMock(FieldItemListInterface::class);
    $field->method('isEmpty')->willReturn(TRUE);

    $entity = $this->createMock(\Drupal\Core\Entity\ContentEntityInterface::class);
    $entity->method('hasField')->with('field_transaction_type')->willReturn(TRUE);
    $entity->method('get')->with('field_transaction_type')->willReturn($field);

    $result = $this->matcher->getMatchingRule($entity);

    $this->assertNull($result);
  }

  /**
   * @covers ::getMatchingRule
   */
  public function testGetMatchingRuleReturnsMatchingRule(): void {
    // Mock transaction type field.
    $field = $this->createMock(FieldItemListInterface::class);
    $field->method('isEmpty')->willReturn(FALSE);
    $field->method('__get')->with('value')->willReturn('OFFICE');

    $entity = $this->createMock(\Drupal\Core\Entity\ContentEntityInterface::class);
    $entity->method('hasField')->with('field_transaction_type')->willReturn(TRUE);
    $entity->method('get')->with('field_transaction_type')->willReturn($field);

    // Mock matching rule.
    $rule = $this->createMock(PriceRuleInterface::class);
    $rule->method('getTransactionType')->willReturn('OFFICE');
    $rule->method('getWeight')->willReturn(0);
    $rule->method('id')->willReturn('office_rule');

    $this->storage->method('loadMultiple')
      ->willReturn(['office_rule' => $rule]);

    $result = $this->matcher->getMatchingRule($entity);

    $this->assertSame($rule, $result);
  }

  /**
   * @covers ::getMatchingRule
   */
  public function testGetMatchingRuleWithNoMatch(): void {
    // Mock transaction type field.
    $field = $this->createMock(FieldItemListInterface::class);
    $field->method('isEmpty')->willReturn(FALSE);
    $field->method('__get')->with('value')->willReturn('UNKNOWN');

    $entity = $this->createMock(\Drupal\Core\Entity\ContentEntityInterface::class);
    $entity->method('hasField')->with('field_transaction_type')->willReturn(TRUE);
    $entity->method('get')->with('field_transaction_type')->willReturn($field);

    $this->storage->method('loadMultiple')
      ->willReturn([]);

    $result = $this->matcher->getMatchingRule($entity);

    $this->assertNull($result);
  }

  /**
   * @covers ::getMatchingRule
   */
  public function testGetMatchingRuleReturnsFirstWhenMultiple(): void {
    // Mock transaction type field.
    $field = $this->createMock(FieldItemListInterface::class);
    $field->method('isEmpty')->willReturn(FALSE);
    $field->method('__get')->with('value')->willReturn('OFFICE');

    $entity = $this->createMock(\Drupal\Core\Entity\ContentEntityInterface::class);
    $entity->method('hasField')->with('field_transaction_type')->willReturn(TRUE);
    $entity->method('get')->with('field_transaction_type')->willReturn($field);

    // Mock multiple matching rules.
    $rule1 = $this->createMock(PriceRuleInterface::class);
    $rule1->method('getTransactionType')->willReturn('OFFICE');
    $rule1->method('getWeight')->willReturn(0);
    $rule1->method('id')->willReturn('rule1');

    $rule2 = $this->createMock(PriceRuleInterface::class);
    $rule2->method('getTransactionType')->willReturn('OFFICE');
    $rule2->method('getWeight')->willReturn(10);
    $rule2->method('id')->willReturn('rule2');

    $this->storage->method('loadMultiple')
      ->willReturn(['rule1' => $rule1, 'rule2' => $rule2]);

    $result = $this->matcher->getMatchingRule($entity);

    // Should return rule with lower weight (higher priority).
    $this->assertSame($rule1, $result);
  }

}
